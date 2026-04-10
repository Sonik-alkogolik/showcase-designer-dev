# AgentAI

Переносимое ядро агентов и skills по архитектуре `Core + Project Overlay`.

## Структура

- `core/` — общие агенты, skills и workflows
- `project/` — проектные override/расширения
- `config/agentai.md` — правила загрузки и merge
- `docs/` — архитектурные решения

## Правило Overlay

1. Сначала загружается `core/*`
2. Затем загружается `project/*`
3. При совпадении ID приоритет у `project`

## Формат файлов

В этом шаблоне всё хранится в `.md`.
