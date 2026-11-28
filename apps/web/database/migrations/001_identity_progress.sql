-- Version 001 : socle identite et historique pedagogique.
-- Base MariaDB dediee, vide a la premiere execution.
-- Migration additive : ne supprime aucune donnee existante.
-- Aucun compte ni mot de passe n'est cree par cette migration.

CREATE TABLE lp_account (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    login VARCHAR(190) NOT NULL,
    email VARCHAR(190) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uniq_account_login (login),
    UNIQUE KEY uniq_account_email (email),
    CONSTRAINT chk_account_role CHECK (role IN ('learner', 'guardian', 'administrator'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lp_learner (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    display_name VARCHAR(80) NOT NULL,
    grade_level VARCHAR(16) NOT NULL,
    linked_account_id BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uniq_learner_linked_account (linked_account_id),
    CONSTRAINT fk_learner_account FOREIGN KEY (linked_account_id)
        REFERENCES lp_account (id) ON DELETE SET NULL,
    CONSTRAINT chk_learner_grade CHECK (grade_level IN
        ('CP', 'CE1', 'CE2', 'CM1', 'CM2', '6e', '5e', '4e', '3e', '2de', '1re', 'Tle'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lp_guardian_learner (
    guardian_account_id BIGINT UNSIGNED NOT NULL,
    learner_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (guardian_account_id, learner_id),
    KEY idx_guardian_learner_learner (learner_id),
    CONSTRAINT fk_guardian_learner_account FOREIGN KEY (guardian_account_id)
        REFERENCES lp_account (id) ON DELETE CASCADE,
    CONSTRAINT fk_guardian_learner_learner FOREIGN KEY (learner_id)
        REFERENCES lp_learner (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lp_attempt (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    learner_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    template_slug VARCHAR(100) NOT NULL,
    template_version SMALLINT UNSIGNED NOT NULL,
    difficulty_tier TINYINT UNSIGNED NOT NULL,
    stars TINYINT UNSIGNED NOT NULL,
    parameters_json JSON NOT NULL,
    answers_json JSON NOT NULL,
    assessment_json JSON NOT NULL,
    assessed_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uniq_learner_attempt (learner_id, token_hash),
    KEY idx_attempt_learner_date (learner_id, assessed_at),
    KEY idx_attempt_learner_template (learner_id, template_slug),
    CONSTRAINT fk_attempt_learner FOREIGN KEY (learner_id)
        REFERENCES lp_learner (id) ON DELETE RESTRICT,
    CONSTRAINT chk_attempt_stars CHECK (stars BETWEEN 0 AND 5),
    CONSTRAINT chk_attempt_tier CHECK (difficulty_tier BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lp_competency_progress (
    learner_id BIGINT UNSIGNED NOT NULL,
    group_code VARCHAR(40) NOT NULL,
    difficulty_tier TINYINT UNSIGNED NOT NULL DEFAULT 1,
    attempts_count INT UNSIGNED NOT NULL DEFAULT 0,
    best_stars TINYINT UNSIGNED NOT NULL DEFAULT 0,
    recent_scores_json JSON NOT NULL,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (learner_id, group_code),
    CONSTRAINT fk_competency_learner FOREIGN KEY (learner_id)
        REFERENCES lp_learner (id) ON DELETE RESTRICT,
    CONSTRAINT chk_competency_tier CHECK (difficulty_tier BETWEEN 1 AND 3),
    CONSTRAINT chk_competency_best CHECK (best_stars BETWEEN 0 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
