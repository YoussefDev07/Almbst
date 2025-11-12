-- Migration: create exams, questions, results, answers tables

CREATE TABLE IF NOT EXISTS exams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  num_questions INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_id INT NOT NULL,
  q_image VARCHAR(255) DEFAULT NULL,
  choice_a TEXT,
  choice_b TEXT,
  choice_c TEXT,
  choice_d TEXT,
  correct_choice CHAR(1) NOT NULL,
  position INT DEFAULT 0,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(28) NOT NULL,
  exam_id INT NOT NULL,
  score VARCHAR(4) NOT NULL DEFAULT 0,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  total_time_seconds INT NOT NULL DEFAULT 0,
  allowed_seconds INT NOT NULL DEFAULT 0,
  FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  result_id INT NOT NULL,
  question_id INT NOT NULL,
  selected_choice CHAR(1),
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  time_taken_seconds INT NOT NULL DEFAULT 0,
  FOREIGN KEY (result_id) REFERENCES results(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;