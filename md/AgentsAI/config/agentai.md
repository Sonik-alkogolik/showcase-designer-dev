# AgentAI Config

## Meta

- version: 1
- name: AgentAI
- format: markdown-only

## Loader

- merge_order: core -> project
- resolution: overlay
- conflict_policy: project_wins

## Paths

- core_agents: `./core/agents`
- core_skills: `./core/skills`
- core_workflows: `./core/workflows`
- project_agents: `./project/agents`
- project_skills: `./project/skills`
- project_workflows: `./project/workflows`

## Naming

- agent definition: `agent.md`
- skill definition: `SKILL.md`
- workflow definition: `*.workflow.md`

## Frontmatter Standard

Each definition file must start with YAML-like frontmatter:

```md
---
id: example
name: Example
---
```

Required fields:

- Agent: `id`, `purpose`, `inputs`, `outputs`
- Skill: `id`, `intent`
- Workflow: `id`, `steps`, `exit_criteria`

## Validation Rules

- Frontmatter is parsed first
- Markdown sections are fallback for backward compatibility
