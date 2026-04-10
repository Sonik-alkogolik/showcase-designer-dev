# AgentAI Roadmap

## Vision

AgentAI evolves as a portable core (`core`) that can be cloned into any project and immediately used with strong validation, consistent workflows, and language-specialized agents.

## Versioning Policy

- Format: `MAJOR.MINOR.PATCH`
- MAJOR: breaking changes in structure/standards
- MINOR: new agents, skills, workflows, tooling features
- PATCH: fixes, docs improvements, non-breaking refinements

## Release Cadence

- Small iterative releases to `main`
- Each release is documented in `CHANGELOG.md`
- Every structural change must update this roadmap or mark item complete

## Milestones

## v0.1.x Foundation (Current)

- [x] Core + Project Overlay structure
- [x] Markdown-only definitions
- [x] Frontmatter standard
- [x] Loader (`agentai-loader.ps1`)
- [x] Doctor validator (`doctor.ps1`)
- [x] Base agents: planner, coder, reviewer, qa
- [x] Language agents: php, python, java, js
- [x] Base + language skills

## v0.2.x Governance and Reliability

- [ ] Add strict schema doc for frontmatter keys/types
- [ ] Add link validation: `agent.linked_skills` must exist
- [ ] Add workflow validation: all `steps` must map to existing agents
- [ ] Add duplicate ID check report with remediation hints
- [ ] Add `scripts/check.ps1` (doctor + loader + summary)

## v0.3.x Language Depth Packs

- [ ] PHP deep pack: Laravel, Symfony, WordPress, Bitrix playbooks
- [ ] Python deep pack: FastAPI/Django + data/AI runbooks
- [ ] Java deep pack: Spring patterns + integration templates
- [ ] JS deep pack: frontend/backend architecture recipes
- [ ] Add framework-specific workflow templates

## v0.4.x Delivery System

- [ ] Introduce release manifest (`releases/*.md`)
- [ ] Add migration notes template for breaking changes
- [ ] Add automated changelog helper script
- [ ] Add quality scorecard per release

## v1.0.0 Stable Core

Definition of done:

- Portable and stable architecture
- Reliable validation and diagnostics
- Mature language packs
- Reproducible release process
- Clear docs for onboarding and contribution

## Working Rules

- One small change per commit whenever possible
- Update `CHANGELOG.md` for every merged change
- Keep docs synchronized with implementation
- Prefer additive changes; document all breaking changes explicitly
