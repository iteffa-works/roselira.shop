---
name: commit-flowaxy
description: Create semantic git commits for roselira.shop. Use when user asks for git commit(s) or to split changes into reviewable commits.
---

# Commits (Flowaxy)

## Rules

- One logical concern per commit
- Prefix: `fix:`, `feat:`, `refactor:`, `docs:`, `chore:`
- Never commit `.env`, keys, `storage/roselira.db`
- Do not push unless asked

## Examples

```
fix(routes): import SeoController for sitemap handler
refactor(support): shared format helpers and cron interval
docs: add .docs knowledge base and trim README
chore(cursor): add AGENTS.md, rules and project skills
```

## Before commit

```bash
git status
git diff
git log -3 --oneline
```

Group related files; write message focusing on **why**.
