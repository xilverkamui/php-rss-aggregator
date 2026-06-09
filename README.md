# PHP RSS Aggregator

A simple PHP-based RSS aggregator that merges multiple RSS feeds into a single verified RSS feed.

Generated feeds are published as static XML files and can be hosted using GitHub Pages.

## Features

* Merge multiple RSS feeds
* Sort items by publish date
* Deduplicate items
* Config-driven
* Per-source customization
* XML output with proper formatting
* Generate single channel or all channels
* Compatible with shared hosting and GitHub Actions
* GitHub Pages friendly

---

## Project Structure

```text
php-rss-aggregator/
│
├── .github/
│   └── workflows/
│       └── generate-infosurabayaterkini.yml
│
├── cache/
│   └── rss/
│
├── data/
│   └── infosurabayaterkini.xml
│
├── helpers.php
├── rss.php
├── generate.php
├── config.json
├── composer.json
└── README.md
```

---

## Installation

Clone repository:

```bash
git clone https://github.com/xilverkamui/php-rss-aggregator.git
```

Install dependencies:

```bash
composer install
```

Create cache directory:

```bash
mkdir -p cache/rss
```

---

## Generate RSS

Generate all channels:

```bash
php generate.php
```

Generate a specific channel:

```bash
php generate.php infosurabayaterkini
```

---

## Preview RSS

Browser:

```text
generate.php?channel=infosurabayaterkini&mode=preview
```

CLI:

```bash
php generate.php infosurabayaterkini preview
```

---

## Configuration

All settings are stored in:

```text
config.json
```

Each channel contains:

* title
* description
* feed_link
* output_file
* cache_minutes
* max_items
* replace_rules
* sources

Example:

```json
{
  "channels": {
    "infosurabayaterkini": {
      "title": "Info Surabaya Terkini",
      "feed_link": "https://xilverkamui.github.io/php-rss-aggregator/data/infosurabayaterkini.xml",
      "output_file": "infosurabayaterkini.xml",
      "enabled": true,
      "sources": [
        {
          "name": "Suarasurabaya",
          "url": "https://www.suarasurabaya.net/feed/",
          "enabled": true
        }
      ]
    }
  }
}
```

---

## GitHub Actions

RSS is automatically regenerated every hour using:

```text
.github/workflows/generate-infosurabayaterkini.yml
```

Manual execution is also supported from the Actions tab.

---

## GitHub Pages

After enabling GitHub Pages, the generated RSS feed will be available at:

```text
https://xilverkamui.github.io/php-rss-aggregator/data/infosurabayaterkini.xml
```

This URL can be used directly by RSS readers.

---

## Requirements

* PHP 8.2+
* Composer
* SimplePie

---

## License

MIT
