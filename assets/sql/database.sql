-- CREATE DATABASE

CREATE DATABASE almbst;

-- Create `accounts` Table

CREATE TABLE accounts (
  id varchar(28) UNIQUE,
  via ENUM("google"),
  email varchar(255),
  fristname varchar(100),
  lastname varchar(100),
  avatar varchar(1000),
  created date
);

-- Create `subscriptions` Table

CREATE TABLE subscriptions (
  id int(9) PRIMARY KEY AUTO_INCREMENT,
  course_id int(4),
  user_id varchar(28),
  subscription_date date,
  expire_date date,
  expired boolean DEFAULT false
);

-- Create `courses` Table

CREATE TABLE courses (
  id int(4) PRIMARY KEY AUTO_INCREMENT,
  title varchar(38),
  icon varchar(25),
  duration varchar(6),
  price double(5,2)
);

-- Create `courses_sections` Table

CREATE TABLE courses_sections (
  id int(11) PRIMARY KEY AUTO_INCREMENT,
  type ENUM("category", "element"),
  title varchar(38),
  course_id int(4),
  category_id int(11),
  video text,
  test text
);

-- Create `activate_codes` Table

CREATE TABLE activate_codes (
  id int(22) PRIMARY KEY AUTO_INCREMENT,
  code varchar(13)
);