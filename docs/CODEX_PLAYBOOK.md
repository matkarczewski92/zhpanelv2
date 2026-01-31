# CODEX PLAYBOOK — Laravel Architecture Rules

This document defines mandatory architectural rules for this project.
Codex MUST read and follow this document before generating or modifying code.

---

## 1. General Architecture

- This project uses **Service Layer / Application Commands** architecture.
- Business logic MUST NOT be placed in Controllers or Eloquent Models.
- Controllers are thin. Services (Commands) are fat.
- Database schema is considered LEGACY and MUST be respected.

---

## 2. Controllers

Controllers may ONLY:
- accept FormRequest-validated input
- call exactly one Application Command or Query
- return HTTP responses (JSON / views)

Controllers MUST NOT:
- contain business rules
- access models directly for writes
- perform calculations or decisions

---

## 3. Eloquent Models

Models represent database tables and MUST remain thin.

Allowed in models:
- `$table`, `$fillable`, `$casts`
- relationships (`belongsTo`, `hasMany`, `hasOne`)
- query scopes
- accessors / mutators (no domain logic)

Forbidden in models:
- business logic
- conditional workflows
- cross-table operations
- state transitions

Relationships MUST:
- be defined explicitly
- use explicit foreign keys if not conventional
- match the FK definitions from DB_RELATIONSHIPS.md

---

## 4. Application Layer (Use Cases)

All business logic MUST be implemented as Application Commands or Queries.

Location:
- `app/Application/<Module>/Commands`
- `app/Application/<Module>/Queries`

Rules:
- One command = one use case
- Public API: `handle(array $data): void|DTO`
- Commands MAY use transactions
- Commands MAY call repositories
- Commands MUST dispatch Domain Events after state change

Examples:
- RegisterAnimal
- RecordFeeding
- AddWeight
- PublishOffer
- ReserveOffer
- StartWintering
- AdvanceWinteringStage

---

## 5. Domain Events

Domain Events represent things that HAVE HAPPENED.

Location:
- `app/Domain/Events`
- `app/Listeners`

Rules:
- Events are immutable
- Events are dispatched AFTER successful transaction commit
- Listeners must not change domain state
- Heavy work MUST be delegated to queued Jobs

Examples:
- AnimalRegistered
- FeedingRecorded
- WeightAdded
- OfferReserved
- WinteringStageAdvanced

---

## 6. State Pattern (Mandatory)

The following modules MUST use State pattern:
- Projects / project_stages / project_stages_nfs
- Winterings / winterings_stage

Rules:
- State transitions must be explicit
- Invalid transitions must throw exceptions
- State logic must not live in models

---

## 7. Strategy Pattern

Species/type-specific logic MUST be implemented using Strategy pattern.

Mandatory strategies:
- FeedingStrategy
- BreedingStrategy
- WinteringStrategy

Rules:
- Strategies are selected by animal_type, category, or profile
- No if/else chains based on species in commands

---

## 8. Repositories and Queries

Repositories are REQUIRED for:
- complex filtering
- cross-table reads
- dashboards and reports

Location:
- `app/Domain/<Module>/<Entity>RepositoryInterface.php`
- `app/Infrastructure/Persistence/*Repository.php`

Queries MUST:
- live in Application/Queries
- never be embedded in controllers

---

## 9. Database Rules

- Do NOT rename tables or columns
- Do NOT drop existing constraints
- Do NOT change ON DELETE / ON UPDATE semantics
- SoftDeletes MAY be used only if `deleted_at` column exists
- FK cascade behavior must be respected, not reimplemented in code

---

## 10. Testing Rules

- Write tests for Application Commands
- Do NOT test controllers or Eloquent models directly
- Focus on business rules and state transitions

---

## 11. Codex Generation Rules (STRICT)

Before generating code, Codex MUST:
1. Read this file
2. Read `docs/DB_RELATIONSHIPS.md`
3. Use FK map as source of truth
4. Never invent relationships
5. Never put logic in models or controllers

Violation of these rules is considered an error.


## Panel vs Admin – Routing and Responsibility Rules (MANDATORY)

This application distinguishes between two separate areas:

### 1. PANEL (Main Breeding Panel)
The PANEL is the primary application used for day-to-day breeding operations.

PANEL includes:
- Animals (CRUD)
- Feedings, weights, molts
- Litters / breeding
- Winterings (operations on animals)
- Offers / sales
- Finances
- Projects and stages

Rules:
- PANEL routes MUST NOT be considered "admin" routes.
- PANEL represents operational data and daily workflows.
- PANEL routes will eventually live under a dedicated prefix (e.g. /panel) and name prefix (panel.*).
- PANEL routes must be organized per module (animals, litters, winterings, etc.), not in a single file.

---

### 2. ADMIN (Configuration Only)
ADMIN is strictly limited to configuration and system setup.

ADMIN includes ONLY:
- Dictionaries / lookup tables (animal_category, animal_type, feeds if configurable)
- Genotype configuration (categories, traits, mappings)
- Wintering stage definitions (winterings_stage)
- System configuration (system_config)
- Other static or semi-static configuration affecting the panel behavior

Rules:
- ADMIN must NEVER contain breeding or operational logic.
- ADMIN routes must NOT modify or operate on animals directly.
- ADMIN is accessed only via a settings/configuration entry in the UI (e.g. gear icon).
- ADMIN routes will eventually live under a dedicated prefix (e.g. /admin) and name prefix (admin.*).
- ADMIN routes must be organized per configuration module, not in a single file.

---

### 3. Routing Structure (Future Requirement)

When routes are introduced, the following structure is REQUIRED:

- Routes MUST be split by responsibility and module.
- A single routes file for the entire application is NOT allowed.

Expected structure:
- routes/panel/<module>.php  (animals, litters, winterings, etc.)
- routes/admin/<module>.php  (dictionaries, genotype, system, etc.)

Bootstrap files may be used (e.g. routes/panel/_panel.php, routes/admin/_admin.php)
to group prefixes, middleware, and naming.

---

All tables/list-group rows in panel views must use transparent backgrounds and light 8% border lines (rgba(255,255,255,0.08)); never use opaque card/table backgrounds unless explicitly required.


### 4. Absolute Rule

If a feature involves:
- day-to-day breeding work → it belongs to PANEL
- configuration of how the panel works → it belongs to ADMIN

If there is any doubt, the feature MUST default to PANEL.
