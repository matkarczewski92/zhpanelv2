# Database Relationships Map

This document defines logical and physical relationships between database tables.
Relationships are based on actual FOREIGN KEY constraints and *_id columns.

This file is the single source of truth for Eloquent relationships.

---

## 1. Animals (Core Aggregate)

### animals
- animal_category_id → animal_category.id  
  ON DELETE CASCADE | ON UPDATE CASCADE
- animal_type_id → animal_type.id  
  ON DELETE CASCADE | ON UPDATE CASCADE
- feed_id → feeds.id  
  ON DELETE SET NULL | ON UPDATE CASCADE
- litter_id → litters.id  
  ON DELETE SET NULL | ON UPDATE CASCADE

### animal_category
- hasMany animals

### animal_type
- hasMany animals

---

## 2. Feeding, Weights, Molts, Gallery

### animal_feedings
- animal_id → animals.id
- feed_id → feeds.id

### animal_weights
- animal_id → animals.id

### animal_molts
- animal_id → animals.id

### animal_photo_gallery
- animal_id → animals.id

### feeds
- hasMany animal_feedings
- hasMany animals (default feed)

---

## 3. Offers and Reservations

### animal_offers
- animal_id → animals.id

### animal_offer_reservations
- offer_id → animal_offers.id

---

## 4. Genotype / Traits

### animal_genotype
- animal_id → animals.id
- genotype_id → animal_genotype_category.id

### animal_genotype_category
- hasMany animal_genotype
- hasMany animal_genotype_traits_dictionary

### animal_genotype_traits_dictionary
- trait_id → animal_genotype_traits.id
- category_id → animal_genotype_category.id

### animal_genotype_traits
- hasMany animal_genotype_traits_dictionary

---

## 5. Litters / Breeding

### litters
- hasMany animals
- hasMany litters_pairings
- hasMany litters_adnotations
- hasMany litters_gallery

### litters_adnotations
- litter_id → litters.id

### litters_gallery
- litter_id → litters.id

### litters_pairings
- litter_id → litters.id

### litters_pairings_summary
- pairings_id → litters_pairings.id
- litter_id → litters.id

### litter_plans
- hasMany litter_plan_pairs

### litter_plan_pairs
- litter_plan_id → litter_plans.id
- female_id → animals.id
- male_id → animals.id

### not_for_sales
- pairing_id → litters_pairings.id

---

## 6. Projects and Stages (State Machine)

### projects
- hasMany projects_stages
- hasMany project_annotations

### projects_stages
- project_id → projects.id
- parent_male_id → animals.id
- parent_female_id → animals.id

### projects_stages_nfs
- stage_id → projects_stages.id

### project_annotations
- project_id → projects.id

---

## 7. Winterings (State Machine)

### winterings
- animal_id → animals.id
- stage_id → winterings_stage.id

### winterings_stage
- hasMany winterings

---

## 8. Finances

### finances
- finances_category_id → finances_category.id
- animal_id → animals.id
- feed_id → feeds.id

### finances_category
- hasMany finances

---

## 9. System / Frontend Tables

### system_config
- standalone (system configuration)

### web_gallery
- standalone (frontend content)

### web_news
- standalone (frontend content)

---

## Implementation Notes

- All Eloquent relationships MUST follow this map.
- Foreign keys MUST be specified explicitly where needed.
- Cascade behavior is handled by the database, not by model events.
- State-related tables MUST be wrapped by State pattern abstractions.
