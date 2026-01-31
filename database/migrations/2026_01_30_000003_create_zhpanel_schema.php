<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `animals` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sex` int NOT NULL,
  `date_of_birth` date NOT NULL,
  `animal_type_id` bigint UNSIGNED DEFAULT NULL,
  `litter_id` bigint UNSIGNED DEFAULT NULL,
  `feed_id` bigint UNSIGNED DEFAULT NULL,
  `feed_interval` int DEFAULT NULL,
  `animal_category_id` bigint UNSIGNED DEFAULT NULL,
  `public_profile` int NOT NULL DEFAULT '0',
  `public_profile_tag` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `web_gallery` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animals_animal_type_id_foreign` (`animal_type_id`),
  KEY `animals_litter_id_foreign` (`litter_id`),
  KEY `animals_feed_id_foreign` (`feed_id`),
  KEY `animals_animal_category_id_foreign` (`animal_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_category` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_feedings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` bigint UNSIGNED NOT NULL,
  `feed_id` bigint UNSIGNED NOT NULL,
  `amount` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_feedings_animal_id_foreign` (`animal_id`),
  KEY `animal_feedings_feed_id_foreign` (`feed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_genotype` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `genotype_id` bigint UNSIGNED NOT NULL,
  `animal_id` bigint UNSIGNED NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'v-homozygota, h-heterozygota, p-poshet',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_genotype_genotype_id_foreign` (`genotype_id`),
  KEY `animal_genotype_animal_id_foreign` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_genotype_category` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gene_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gene_type` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_genotype_traits` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_traits` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_genotype_traits_dictionary` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `trait_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_genotype_traits_dictionary_trait_id_foreign` (`trait_id`),
  KEY `animal_genotype_traits_dictionary_category_id_foreign` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_molts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_molts_animal_id_foreign` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_offers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` bigint UNSIGNED NOT NULL,
  `price` double(8,2) NOT NULL,
  `sold_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_offers_animal_id_foreign` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_offer_reservations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `offer_id` bigint UNSIGNED NOT NULL,
  `deposit` double(8,2) DEFAULT NULL,
  `booker` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adnotations` text COLLATE utf8mb4_unicode_ci,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_offer_reservations_offer_id_foreign` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_photo_gallery` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` bigint UNSIGNED NOT NULL,
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_profil_photo` int NOT NULL DEFAULT '0',
  `banner_possition` int NOT NULL DEFAULT '50',
  `webside` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_photo_gallery_animal_id_foreign` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_type` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `animal_weights` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` bigint UNSIGNED NOT NULL,
  `value` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `animal_weights_animal_id_foreign` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `feeds` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feeding_interval` int NOT NULL,
  `amount` int NOT NULL,
  `last_price` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `finances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `finances_category_id` bigint UNSIGNED NOT NULL,
  `amount` double(8,2) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `feed_id` bigint UNSIGNED DEFAULT NULL,
  `animal_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finances_finances_category_id_foreign` (`finances_category_id`),
  KEY `finances_feed_id_foreign` (`feed_id`),
  KEY `finances_animal_id_foreign` (`animal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `finances_category` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litters` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` int NOT NULL COMMENT '0-miot, 1-planowane, 2-możliwe, 3-zrealizowane',
  `litter_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection_date` date DEFAULT NULL,
  `laying_date` date DEFAULT NULL,
  `hatching_date` date DEFAULT NULL,
  `laying_eggs_total` int DEFAULT NULL,
  `laying_eggs_ok` int DEFAULT NULL,
  `hatching_eggs` int DEFAULT NULL,
  `season` int DEFAULT NULL,
  `adnotations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_male` bigint UNSIGNED DEFAULT NULL,
  `parent_female` bigint UNSIGNED DEFAULT NULL,
  `planned_connection_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `litters_parent_male_foreign` (`parent_male`),
  KEY `litters_parent_female_foreign` (`parent_female`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litters_adnotations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `litter_id` bigint UNSIGNED NOT NULL,
  `adnotation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `litters_adnotations_litter_id_foreign` (`litter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litters_gallery` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `litter_id` bigint UNSIGNED NOT NULL,
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_photo` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `litters_gallery_litter_id_foreign` (`litter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litters_pairings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `percent` int NOT NULL,
  `title_vis` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_het` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `litter_id` bigint UNSIGNED DEFAULT NULL,
  `value` int DEFAULT NULL,
  `img_url` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `litters_pairings_litter_id_foreign` (`litter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litters_pairings_summary` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `pairings_id` bigint UNSIGNED NOT NULL,
  `litter_id` bigint UNSIGNED NOT NULL,
  `vis_amount` int NOT NULL,
  `het_amount` int NOT NULL,
  `scaleless` tinyint(1) NOT NULL,
  `tessera` tinyint(1) NOT NULL,
  `stripe` tinyint(1) NOT NULL,
  `motley` tinyint(1) NOT NULL,
  `okeetee` tinyint(1) NOT NULL,
  `extreme_okeetee` tinyint(1) NOT NULL,
  `multiplier` double(1,1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `litters_pairings_summary_pairings_id_foreign` (`pairings_id`),
  KEY `litters_pairings_summary_litter_id_foreign` (`litter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litter_plans` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `planned_year` smallint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `litter_plan_pairs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `litter_plan_id` bigint UNSIGNED NOT NULL,
  `female_id` bigint UNSIGNED NOT NULL,
  `male_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `litter_plan_pair_unique` (`litter_plan_id`,`female_id`,`male_id`),
  KEY `litter_plan_pairs_female_id_foreign` (`female_id`),
  KEY `litter_plan_pairs_male_id_foreign` (`male_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `not_for_sales` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `pairing_id` bigint UNSIGNED NOT NULL,
  `sex` int NOT NULL,
  `annotations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `not_for_sales_pairing_id_foreign` (`pairing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `projects` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `projects_stages` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `season` int NOT NULL,
  `project_id` bigint UNSIGNED NOT NULL,
  `parent_male_id` bigint UNSIGNED DEFAULT NULL,
  `parent_male_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_female_id` bigint UNSIGNED DEFAULT NULL,
  `parent_female_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_stages_project_id_foreign` (`project_id`),
  KEY `projects_stages_parent_male_id_foreign` (`parent_male_id`),
  KEY `projects_stages_parent_female_id_foreign` (`parent_female_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `projects_stages_nfs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `stage_id` bigint UNSIGNED NOT NULL,
  `percent` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sex` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_stages_nfs_stage_id_foreign` (`stage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `project_annotations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint UNSIGNED NOT NULL,
  `annotations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_annotations_project_id_foreign` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `system_config` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `web_gallery` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `winterings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` bigint UNSIGNED NOT NULL,
  `season` int NOT NULL,
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `annotations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stage_id` bigint UNSIGNED DEFAULT NULL,
  `custom_duration` int DEFAULT NULL,
  `archive` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `winterings_animal_id_foreign` (`animal_id`),
  KEY `winterings_stage_id_foreign` (`stage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
CREATE TABLE `winterings_stage` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `order` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animals`
  ADD CONSTRAINT `animals_animal_category_id_foreign` FOREIGN KEY (`animal_category_id`) REFERENCES `animal_category` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `animals_animal_type_id_foreign` FOREIGN KEY (`animal_type_id`) REFERENCES `animal_type` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `animals_feed_id_foreign` FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `animals_litter_id_foreign` FOREIGN KEY (`litter_id`) REFERENCES `litters` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_feedings`
  ADD CONSTRAINT `animal_feedings_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `animal_feedings_feed_id_foreign` FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_genotype`
  ADD CONSTRAINT `animal_genotype_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `animal_genotype_genotype_id_foreign` FOREIGN KEY (`genotype_id`) REFERENCES `animal_genotype_category` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_genotype_traits_dictionary`
  ADD CONSTRAINT `animal_genotype_traits_dictionary_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `animal_genotype_category` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `animal_genotype_traits_dictionary_trait_id_foreign` FOREIGN KEY (`trait_id`) REFERENCES `animal_genotype_traits` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_molts`
  ADD CONSTRAINT `animal_molts_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_offers`
  ADD CONSTRAINT `animal_offers_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_offer_reservations`
  ADD CONSTRAINT `animal_offer_reservations_offer_id_foreign` FOREIGN KEY (`offer_id`) REFERENCES `animal_offers` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_photo_gallery`
  ADD CONSTRAINT `animal_photo_gallery_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `animal_weights`
  ADD CONSTRAINT `animal_weights_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `finances`
  ADD CONSTRAINT `finances_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `finances_feed_id_foreign` FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `finances_finances_category_id_foreign` FOREIGN KEY (`finances_category_id`) REFERENCES `finances_category` (`id`);
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `litters`
  ADD CONSTRAINT `litters_parent_female_foreign` FOREIGN KEY (`parent_female`) REFERENCES `animals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `litters_parent_male_foreign` FOREIGN KEY (`parent_male`) REFERENCES `animals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `litters_gallery`
  ADD CONSTRAINT `litters_gallery_litter_id_foreign` FOREIGN KEY (`litter_id`) REFERENCES `litters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `litters_pairings`
  ADD CONSTRAINT `litters_pairings_litter_id_foreign` FOREIGN KEY (`litter_id`) REFERENCES `litters` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `litters_pairings_summary`
  ADD CONSTRAINT `litters_pairings_summary_litter_id_foreign` FOREIGN KEY (`litter_id`) REFERENCES `litters` (`id`),
  ADD CONSTRAINT `litters_pairings_summary_pairings_id_foreign` FOREIGN KEY (`pairings_id`) REFERENCES `litters_pairings` (`id`);
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `litter_plan_pairs`
  ADD CONSTRAINT `litter_plan_pairs_female_id_foreign` FOREIGN KEY (`female_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `litter_plan_pairs_litter_plan_id_foreign` FOREIGN KEY (`litter_plan_id`) REFERENCES `litter_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `litter_plan_pairs_male_id_foreign` FOREIGN KEY (`male_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `not_for_sales`
  ADD CONSTRAINT `not_for_sales_pairing_id_foreign` FOREIGN KEY (`pairing_id`) REFERENCES `litters_pairings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `projects_stages`
  ADD CONSTRAINT `projects_stages_parent_female_id_foreign` FOREIGN KEY (`parent_female_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_stages_parent_male_id_foreign` FOREIGN KEY (`parent_male_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projects_stages_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `projects_stages_nfs`
  ADD CONSTRAINT `projects_stages_nfs_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `projects_stages` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `project_annotations`
  ADD CONSTRAINT `project_annotations_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
SQL
        );

        DB::statement(<<<'SQL'
ALTER TABLE `winterings`
  ADD CONSTRAINT `winterings_animal_id_foreign` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `winterings_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `winterings_stage` (`id`) ON DELETE CASCADE;
SQL
        );

    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('winterings_stage');
        Schema::dropIfExists('winterings');
        Schema::dropIfExists('web_gallery');
        Schema::dropIfExists('system_config');
        Schema::dropIfExists('project_annotations');
        Schema::dropIfExists('projects_stages_nfs');
        Schema::dropIfExists('projects_stages');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('not_for_sales');
        Schema::dropIfExists('litter_plan_pairs');
        Schema::dropIfExists('litter_plans');
        Schema::dropIfExists('litters_pairings_summary');
        Schema::dropIfExists('litters_pairings');
        Schema::dropIfExists('litters_gallery');
        Schema::dropIfExists('litters_adnotations');
        Schema::dropIfExists('litters');
        Schema::dropIfExists('finances_category');
        Schema::dropIfExists('finances');
        Schema::dropIfExists('feeds');
        Schema::dropIfExists('animal_weights');
        Schema::dropIfExists('animal_type');
        Schema::dropIfExists('animal_photo_gallery');
        Schema::dropIfExists('animal_offer_reservations');
        Schema::dropIfExists('animal_offers');
        Schema::dropIfExists('animal_molts');
        Schema::dropIfExists('animal_genotype_traits_dictionary');
        Schema::dropIfExists('animal_genotype_traits');
        Schema::dropIfExists('animal_genotype_category');
        Schema::dropIfExists('animal_genotype');
        Schema::dropIfExists('animal_feedings');
        Schema::dropIfExists('animal_category');
        Schema::dropIfExists('animals');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};