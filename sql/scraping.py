import urllib.request
import json
import time

# Jikan API manga IDs matching our seeded titles
MANGA_TARGETS = [
    {"id": 820,  "title": "Nausicaa of the Valley of the Wind", "studio": "Studio Ghibli"},
    {"id": 2246, "title": "Whisper of the Heart",               "studio": "Studio Ghibli"},
    {"id": 2251, "title": "My Neighbor Totoro",                 "studio": "Studio Ghibli"},
    {"id": 116778,"title": "Chainsaw Man",                      "studio": "MAPPA"},
    {"id": 23390, "title": "Attack on Titan",                   "studio": "MAPPA"},
    {"id": 4632,  "title": "Vinland Saga",                      "studio": "MAPPA"},
    {"id": 113138,"title": "Jujutsu Kaisen",                    "studio": "MAPPA"},
    {"id": 96792, "title": "Demon Slayer",                      "studio": "ufotable"},
    {"id": 25,    "title": "Fullmetal Alchemist",               "studio": "Studio Bones"},
    {"id": 75989, "title": "My Hero Academia",                  "studio": "Studio Bones"},
]

BASE_URL = "https://api.jikan.moe/v4/manga/"


def fetch_manga(manga_id: int) -> dict:
    url = f"{BASE_URL}{manga_id}"
    req = urllib.request.Request(url, headers={"Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=10) as res:
        return json.loads(res.read().decode())["data"]


def extract(raw: dict, studio: str) -> dict:
    authors = raw.get("authors", [])
    mangaka = authors[0]["name"] if authors else "Unknown"

    genres = [g["name"] for g in raw.get("genres", [])]

    published = raw.get("published", {})
    publish_date = (published.get("prop", {})
                             .get("from", {})
                             .get("year"))

    return {
        "mal_id":       raw["mal_id"],
        "title":        raw.get("title_english") or raw.get("title"),
        "synopsis":     (raw.get("synopsis") or "")[:500],
        "mangaka":      mangaka,
        "publish_year": publish_date,
        "genres":       genres,
        "volumes":      raw.get("volumes"),
        "score":        raw.get("score"),
        "studio":       studio,
    }


def main():
    results = []
    print(f"Fetching {len(MANGA_TARGETS)} manga entries from MyAnimeList...\n")

    for target in MANGA_TARGETS:
        try:
            print(f"  [{target['id']}] {target['title']}...", end=" ")
            raw  = fetch_manga(target["id"])
            data = extract(raw, target["studio"])
            results.append(data)
            print(f"OK — {data['mangaka']} | {', '.join(data['genres'][:3])}")
        except Exception as e:
            print(f"FAILED — {e}")
            results.append({"mal_id": target["id"], "title": target["title"], "error": str(e)})

        time.sleep(0.4)  # Jikan rate limit: max 3 req/sec

    with open("scraped_data.json", "w", encoding="utf-8") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    print(f"\nDone. Saved {len(results)} records to scraped_data.json")


if __name__ == "__main__":
    main()