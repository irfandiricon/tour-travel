<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Review text in three languages: `review` becomes `review_en` (required, data kept) and
 * `review_id` / `review_ms` are added (optional; empty falls back to English on the site).
 * Idempotent: each step only runs when needed.
 */
class AddLanguagesToTestimonialReview extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('review', 'c_testimonials') && ! $this->db->fieldExists('review_en', 'c_testimonials')) {
            $this->db->query('ALTER TABLE `c_testimonials` CHANGE `review` `review_en` TEXT NOT NULL');
        }
        if (! $this->db->fieldExists('review_id', 'c_testimonials')) {
            $this->db->query('ALTER TABLE `c_testimonials` ADD `review_id` TEXT NULL AFTER `review_en`');
        }
        if (! $this->db->fieldExists('review_ms', 'c_testimonials')) {
            $this->db->query('ALTER TABLE `c_testimonials` ADD `review_ms` TEXT NULL AFTER `review_id`');
        }
    }

    public function down()
    {
        foreach (['review_ms', 'review_id'] as $field) {
            if ($this->db->fieldExists($field, 'c_testimonials')) {
                $this->forge->dropColumn('c_testimonials', $field);
            }
        }
        if ($this->db->fieldExists('review_en', 'c_testimonials') && ! $this->db->fieldExists('review', 'c_testimonials')) {
            $this->db->query('ALTER TABLE `c_testimonials` CHANGE `review_en` `review` TEXT NOT NULL');
        }
    }
}