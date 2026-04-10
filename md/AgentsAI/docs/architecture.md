# Architecture: Core + Project Overlay

## Цель

Копировать папку `AgentAI` в любой проект и сразу получать рабочий слой агентов и skills.

## Модель объединения

- `core` задает baseline
- `project` переопределяет и расширяет
- effective state формируется как merge(core, project)

## ID-правило

Идентификатор сущности равен имени папки.

Пример:
- `core/agents/planner`
- `project/agents/planner` (override)

## Масштабирование

- Новые универсальные сущности добавлять в `core`
- Специфику проекта добавлять в `project`
- Workflows держать короткими и переиспользуемыми
