# CODEX ENTRY POINT

This is the PRIMARY entry point for all Codex-generated code.

Before generating or modifying any code, Codex MUST:
1. Read this file completely.
2. Follow all instructions contained here.
3. Read all referenced documents listed below.

Failure to follow these instructions is considered an error.

---

## Mandatory Documents (Read in Order)

1. docs/CODEX_PLAYBOOK.md  
   → Defines architectural rules and coding constraints.

2. docs/DB_RELATIONSHIPS.md  
   → Defines database schema and Eloquent relationships.

---

## Absolute Rules

- If there is any conflict between generated code and these documents,
  THE DOCUMENTS TAKE PRECEDENCE.
- Do not assume defaults.
- Do not invent architecture.
- Do not skip steps.

---

## Reminder

You are working on a Laravel application with:
- Service Layer (Application Commands)
- Domain Layer (pure business logic)
- Repository pattern
- Legacy database schema
- NO LIVEWIRE

Proceed only after fully reading and understanding all referenced documents.
