# 7-Day Organic Sprint (Infobiz, 30 min/day)

Updated: 2026-05-09

## Goal
- Main KPI: registrations.
- Secondary KPIs: replies in threads, link CTR, registrations per comment.

## Single Entry Point
Use one base entry point and only change source markers.

1. Telegram bot deep link:
- `https://t.me/<BOT_USERNAME>?start=src_<source>_d<day>_c<commentNo>`

2. Telegram mini-app link:
- `https://t.me/<BOT_USERNAME>/<APP_SHORT_NAME>?startapp=src_<source>_d<day>_c<commentNo>`

3. Sources for this sprint:
- `tg_chat`
- `vc_comment`
- `dm`

4. Example links:
- `https://t.me/myshopbot?start=src_tg_chat_d1_c1`
- `https://t.me/myshopbot/myshop?startapp=src_vc_comment_d2_c3`

## Day 0 (Preparation, one-time)
1. Replace placeholders:
- `<BOT_USERNAME>`
- `<APP_SHORT_NAME>`
2. Copy templates from `MARKETING_COMMENT_TEMPLATES.md`.
3. Duplicate `MARKETING_TRACKER_TEMPLATE.csv` into a working file:
- `md/MARKETING_TRACKER_WEEK_YYYY-MM-DD.csv`
4. Prepare one mini-case:
- screenshot;
- 3 numbers: setup time, number of steps, first result.

## Daily Routine (7 days, 30 min)
1. 5 min
- find 2-3 active discussions (Telegram/VC) with real pain around sales in Telegram.
2. 20 min
- publish 4-6 useful comments (no spam);
- use one CTA style per comment;
- attach a source-marked link.
3. 5 min
- fill tracker rows immediately after each comment.

## Comment Quality Rule
Format:
- pain;
- short solution;
- micro-proof;
- one CTA.

CTA line:
- `Если нужно, скину рабочую ссылку на mini-app/бот с этим сценарием.`

## Traffic Quality Loop
1. Every 2nd day change only one variable:
- offer headline or first paragraph.
2. Keep only sources with signal:
- replies > 0 or clicks > 0 or registrations > 0.
3. At end of day:
- mark top-2 best comment variants.

## Day-by-Day Plan
1. Day 1
- setup links and tracker;
- publish first 4 comments.
2. Day 2
- 5-6 comments;
- test Offer A and Offer B.
3. Day 3
- 5-6 comments;
- keep only better offer.
4. Day 4
- mini-retro (10 min inside the 30 min block):
- disable weak sources and weak first paragraphs.
5. Day 5
- repeat top template;
- answer all incoming replies.
6. Day 6
- same as day 5;
- test one new intro sentence only.
7. Day 7
- summarize KPIs;
- decide: scale best source or swap channel next week.

## Weekly Success Threshold
- Minimum success:
  - at least 25 published comments for 7 days total;
  - at least 1 source with stable replies and clicks;
  - registrations from at least 2 different source tags.
