# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog.
Versioning follows SemVer (`MAJOR.MINOR.PATCH`).

## [Unreleased]

### Added

- Formal roadmap with versioned milestones in `docs/roadmap.md`.

## [0.1.0] - 2026-03-23

### Added

- Core architecture: `core/` + `project/` overlay model.
- Markdown-only configuration and definitions.
- Frontmatter standard for agents, skills, workflows.
- `scripts/agentai-loader.ps1` for effective config generation.
- `scripts/doctor.ps1` for structural and metadata validation.
- Base agents: `planner`, `coder`, `reviewer`, `qa`.
- Language agents: `php`, `python`, `java`, `js`.
- Skills: base skills + language skills (`skill-php`, `skill-python`, `skill-java`, `skill-js`).
- Initial workflows: `feature_delivery`, `bugfix_delivery`.

### Notes

- Initial repository setup and push to `main` completed.
