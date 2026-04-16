import httpx
import asyncio
from bs4 import BeautifulSoup
import os

class BeautyParser:
    def __init__(self, keyword="Salon"):
        self.keyword = keyword
        self.base_url = "https://statonline.ru/domains"
        self.output = "../results.txt"
        self.headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"
        }

    async def get_page(self, client, page):
        params = {
            "search": self.keyword,
            "tld": "ru",
            "registered": "REGISTERED",
            "page": page
        }
        try:
            resp = await client.get(self.base_url, params=params, timeout=20.0)
            return resp.text if resp.status_code == 200 else None
        except Exception as e:
            print(f"Ошибка на стр {page}: {e}")
            return None

    def parse_html(self, html):
        soup = BeautifulSoup(html, 'html.parser')
        return [a.text.strip().lower() for a in soup.select('td.domain-name a')]

    async def start(self, pages=137):
        async with httpx.AsyncClient(headers=self.headers, follow_redirects=True) as client:
            for p in range(1, pages + 1):
                print(f"Парсим страницу {p}...")
                html = await self.get_page(client, p)
                domains = self.parse_html(html) if html else []
                
                if not domains: break
                
                with open(self.output, "a") as f:
                    for d in domains:
                        f.write(d + "\n")
                
                await asyncio.sleep(1.5)

if __name__ == "__main__":
    parser = BeautyParser()
    asyncio.run(parser.start())
