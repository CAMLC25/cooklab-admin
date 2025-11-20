from fastapi import FastAPI
from pydantic import BaseModel
from typing import List, Optional, Dict, Any
from rapidfuzz import process, fuzz
import re, unicodedata, requests, os, threading, time

app = FastAPI(title="CookLab AI")

# ---------- config ----------
USE_GEMINI = os.getenv("USE_GEMINI", "false").lower() == "true"
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")
OPENAI_BASE_URL = ""  # không dùng GPT nữa

ING_URL = os.getenv("ING_DUMP_URL", "http://web/api/ai/ingredients-dump")
LARAVEL_SEARCH_URL = os.getenv("LARAVEL_SEARCH_URL", "http://web/api/ai/search")

# ---------- normalize helpers ----------
def _nd(s: str) -> str:
    s = unicodedata.normalize("NFKD", s or "").encode("ascii", "ignore").decode("ascii")
    s = re.sub(r"[^\w\s]", " ", s.lower())
    return re.sub(r"\s+", " ", s).strip()

STOP_TOKENS = set("""toi minh ban co san con nha ve voi lam tu lam voi nau voi mon gi ngon gi nen gi giup goi y chon ra""".split())
UNITS = set("""gram g kg mg ml lit l muong muongcafe muongcanh chen bat dia qua cay cai con mieng lat la cu nhanh goi muongsup tsp tbsp cup canh cafe""".split())
SYN = {
    "ga ta":"ga","ga cong nghiep":"ga","thit ga":"ga","uc ga":"ga","canh ga":"ga","dui ga":"ga",
    "bo":"thit bo","thit bo my":"thit bo","gau bo":"thit bo",
    "heo":"thit heo","lon":"thit heo",
    "tom su":"tom","tom the":"tom",
    "rau can tay":"can tay","hanh la":"hanh la","hanh tay":"hanh tay",
}

def canonicalize(term: str) -> str:
    t = _nd(term)
    t = re.sub(r"\b\d+([\/\.,]\d+)?\b", " ", t)
    toks = [w for w in t.split() if w not in UNITS and w not in STOP_TOKENS]
    t = " ".join(toks).strip()
    return SYN.get(t, t) if t else ""

def tokenize_clean(s: str) -> List[str]:
    s = _nd(s)
    return [t for t in s.split() if t and (t not in STOP_TOKENS) and (t not in UNITS) and not t.isdigit()]

def ngrams(tokens: List[str], n=2) -> List[str]:
    out = []
    for i in range(len(tokens)):
        out.append(tokens[i])
        if i + 1 < len(tokens):
            out.append(tokens[i] + " " + tokens[i+1])
    return out

# ---------- Ingredient lexicon from Laravel ----------
LEXICON: List[str] = []
CANON_MAP: Dict[str, str] = {}

def load_ingredients():
    global LEXICON, CANON_MAP
    try:
        data = requests.get(ING_URL, timeout=10).json().get("data", [])
    except Exception:
        data = []
    normalized = [_nd(x) for x in data if x]
    LEXICON = sorted(set(normalized))
    CANON_MAP = {item: canonicalize(item) for item in LEXICON}

def fuzzy_pick(tokens: List[str], cutoff=78) -> List[str]:
    picked = set()
    for c in tokens:
        q = canonicalize(c)
        if not q:
            continue
        match = process.extractOne(q, LEXICON, scorer=fuzz.WRatio)
        if match and match[1] >= cutoff:
            base = CANON_MAP.get(match[0], q)
            if base: picked.add(base)
    return sorted(picked)

def extract_ingredients_heuristic(text: str) -> List[str]:
    return fuzzy_pick(ngrams(tokenize_clean(text), n=2), cutoff=78)

# ---------- Gemini (optional) ----------
def gemini_client():
    if not (USE_GEMINI and GEMINI_API_KEY):
        return None
    try:
        import google.generativeai as genai
        genai.configure(api_key=GEMINI_API_KEY)
        # 1.5-flash rẻ & nhanh đủ dùng NER nhẹ
        return genai.GenerativeModel("gemini-1.5-flash")
    except Exception:
        return None

def gemini_extract(text: str) -> List[str]:
    model = gemini_client()
    if model is None:
        return []
    prompt = f"""
Bạn là bộ trích xuất NGUYÊN LIỆU. Hãy đọc câu tiếng Việt của người dùng và
trả về DUY NHẤT JSON với định dạng:
{{"ingredients": ["...","..."]}}

YÊU CẦU:
- Chỉ liệt kê tên nguyên liệu (danh từ), không số lượng, không đơn vị.
- Ưu tiên dạng phổ biến: "ga", "thit bo", "thit heo", "tom", "hanh tay", "can tay", ...
- Không liệt kê động từ hay câu dư thừa.
- Nếu không thấy nguyên liệu, trả về mảng rỗng.

Câu người dùng: \"{text}\"
"""
    try:
        resp = model.generate_content(
            prompt,
            generation_config={"response_mime_type": "application/json"}
        )
        data = resp.text.strip()
        import json
        js = json.loads(data)
        arr = js.get("ingredients", [])
        # canonicalize + lọc trùng
        return sorted({canonicalize(x) for x in arr if canonicalize(x)})
    except Exception:
        return []

# ---------- schema ----------
class SearchBody(BaseModel):
    text: Optional[str] = None
    ingredients: Optional[List[str]] = None
    top_k: int = 3

# ---------- startup ----------
@app.on_event("startup")
def _startup():
    def _loader():
        for _ in range(10):
            try:
                load_ingredients()
                if LEXICON: break
            except Exception: pass
            time.sleep(2)
    threading.Thread(target=_loader, daemon=True).start()

@app.get("/health")
def health():
    return {"ok": True, "service": "cooklab-ai", "lexicon_size": len(LEXICON), "use_gemini": USE_GEMINI}

# ---------- search ----------
@app.post("/search")
def search(body: SearchBody) -> Dict[str, Any]:
    # 1) lấy nguyên liệu người dùng nhập (echo lại)
    input_ingredients = body.ingredients or ([] if body.text else [])

    # 2) nguồn nhận diện:
    #    - nếu client gửi ingredients → dùng luôn (sau canonicalize)
    #    - nếu không, thử Gemini → nếu rỗng, fallback heuristic
    if body.ingredients:
        recognized = sorted({canonicalize(x) for x in body.ingredients if canonicalize(x)})
    elif body.text:
        recognized = gemini_extract(body.text) or extract_ingredients_heuristic(body.text)
    else:
        recognized = []

    # 3) gọi Laravel để lấy 3 món (đã sort theo reactions → views → created_at)
    payload = {"ingredients": recognized, "text": body.text or "", "top_k": min(max(body.top_k or 3,1),3)}
    results = []
    try:
        resp = requests.post(LARAVEL_SEARCH_URL, json=payload, timeout=15)
        if resp.ok:
            data = resp.json()
            results = data.get("results", [])
    except Exception as e:
        return {"ok": False, "input_ingredients": input_ingredients, "recognized": recognized, "results": [], "error": str(e)}

    return {
        "ok": True,
        "input_ingredients": input_ingredients,  # NGUYÊN LIỆU NGƯỜI DÙNG GỬI
        "recognized": recognized,                # NGUYÊN LIỆU ĐÃ CHUẨN HÓA/HIỂU
        "results": results                       # 3 công thức phù hợp từ Laravel
    }
