import argparse
import sys
import json
from dramabox_scraper import DramaBoxScraper

def main():
    parser = argparse.ArgumentParser(description="DramaBox Scraper CLI")
    subparsers = parser.add_subparsers(dest="command", help="Commands")

    # Trending command
    subparsers.add_parser("trending", help="Fetch trending dramas")

    # Search command
    search_parser = subparsers.add_parser("search", help="Search for dramas")
    search_parser.add_argument("query", type=str, help="Search query")

    # Details command
    details_parser = subparsers.add_parser("details", help="Fetch drama details")
    details_parser.add_argument("book_id", type=str, help="Book ID")

    # Episodes command
    episodes_parser = subparsers.add_parser("episodes", help="Fetch drama episodes")
    episodes_parser.add_argument("book_id", type=str, help="Book ID")

    args = parser.parse_args()
    scraper = DramaBoxScraper()

    if args.command == "trending":
        results = scraper.get_trending()
        for drama in results:
            print(f"[{drama.get('bookId')}] {drama.get('bookName')}")

    elif args.command == "search":
        results = scraper.search(args.query)
        for drama in results:
            print(f"[{drama.get('bookId')}] {drama.get('bookName')}")

    elif args.command == "details":
        details = scraper.get_details(args.book_id)
        if details:
            print(json.dumps(details, indent=2))
        else:
            print("No details found.")

    elif args.command == "episodes":
        episodes = scraper.get_episodes(args.book_id)
        for ep in episodes:
            print(f"[{ep.get('chapterId')}] {ep.get('chapterName')}")
            cdn_list = ep.get('cdnList', [])
            if cdn_list:
                video_paths = cdn_list[0].get('videoPathList', [])
                if video_paths:
                    # Print the highest quality link available
                    print(f"  Video: {video_paths[0].get('videoPath')}")

    else:
        parser.print_help()

if __name__ == "__main__":
    main()
