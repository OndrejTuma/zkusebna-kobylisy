CREATE TABLE zkusebna_items (
id INT(6) AUTO_INCREMENT,
name VARCHAR(100) NOT NULL,
image VARCHAR(60) NOT NULL,
price INT(4) DEFAULT 0,
reservable tinyint(1) DEFAULT 1,
category ENUM('zkusebna','technika','nastroje') DEFAULT 'technika',
parent_id INT(6),
PRIMARY KEY (id)
)engine=InnoDB;

CREATE TABLE zkusebna_community (
id INT(6) AUTO_INCREMENT,
name VARCHAR(40) NOT NULL,
phone VARCHAR(30) NOT NULL,
email VARCHAR(60) NOT NULL,
PRIMARY KEY (id)
)engine=InnoDB;

CREATE TABLE zkusebna_reservation_purpose (
id INT(6) AUTO_INCREMENT,
title VARCHAR(40) NOT NULL,
discount INT(3) NOT NULL,
PRIMARY KEY (id)
)engine=InnoDB;

CREATE TABLE zkusebna_reservation_repeat (
id INT(6) AUTO_INCREMENT,
type ENUM('WEEKLY','WEEKLY_2','MONTHLY') DEFAULT 'WEEKLY',
repeat_from DATETIME NOT NULL,
repeat_to DATETIME NOT NULL,
PRIMARY KEY (id)
)engine=InnoDB;

CREATE TABLE zkusebna_admin (
id INT(6) AUTO_INCREMENT,
name VARCHAR(10) NOT NULL,
email VARCHAR(32) NOT NULL,
passwd VARCHAR(40) NOT NULL,
hash VARCHAR(100) NOT NULL,
login_time VARCHAR(100) NOT NULL,
PRIMARY KEY (id)
)engine=InnoDB;

CREATE TABLE zkusebna_reservations (
id INT(6) AUTO_INCREMENT,
name VARCHAR(40) NOT NULL,
date_from DATETIME NOT NULL,
date_to DATETIME NOT NULL,
approved tinyint(1) DEFAULT 0,
who INT(6) NOT NULL,
purpose INT(6) NOT NULL DEFAULT 1,
repetition INT(6),
payed tinyint(1) DEFAULT 0,
timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
FOREIGN KEY (who) REFERENCES zkusebna_community(id),
FOREIGN KEY (purpose) REFERENCES zkusebna_reservation_purpose(id),
FOREIGN KEY (repetition) REFERENCES zkusebna_reservation_repeat(id)
)engine=InnoDB;

CREATE TABLE zkusebna_reserved_items (
item_id INT(6) NOT NULL,
reservation_id INT(6) NOT NULL,
active INT(1) DEFAULT 1,
PRIMARY KEY (item_id, reservation_id),
FOREIGN KEY (item_id) REFERENCES zkusebna_items(id),
FOREIGN KEY (reservation_id) REFERENCES zkusebna_reservations(id)
)engine=InnoDB;






INSERT INTO zkusebna_admin (name,email,passwd) VALUES ('admin','ondr@centrum.cz',md5('heslo'));
INSERT INTO zkusebna_reservation_purpose (title,discount) VALUES ('Osobní účely',0), ('Akce farnosti',100);


INSERT INTO zkusebna_items (name,category) VALUES ("Zkušebna", 'zkusebna');
INSERT INTO zkusebna_items (name,reservable) VALUES ("Mixy", 0);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mix velký", 2, 500);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mix malý", 2, 300);
INSERT INTO zkusebna_items (name,reservable) VALUES ("Reproduktory", 0);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Basové kombo", 5, 200);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Reprobedna 1", 5, 150);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Reprobedna 2", 5, 150);
INSERT INTO zkusebna_items (name,reservable) VALUES ("Mikrofony", 0);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mikrofon na zpěv 1", 9, 100);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mikrofon na zpěv 2", 9, 100);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mikrofon na zpěv 3", 9, 100);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mikrofon nástrojový 1", 9, 100);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mikrofon nástrojový 2", 9, 100);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Mikrofon nástrojový 3", 9, 100);
INSERT INTO zkusebna_items (name,reservable) VALUES ("Stojánky", 0);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na mikrofon s ramenem 1", 16, 50);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na mikrofon s ramenem 2", 16, 50);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na mikrofon s ramenem 3", 16, 50);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na mikrofon rovný 1", 16, 30);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na mikrofon rovný 2", 16, 30);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na mikrofon rovný 3", 16, 30);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na noty 1", 16, 20);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na noty 2", 16, 20);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Stojánek na noty 3", 16, 20);
INSERT INTO zkusebna_items (name,reservable) VALUES ("Kabely", 0);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel mikrofonový 1", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel mikrofonový 2", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel mikrofonový 3", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel nástrojový 1", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel nástrojový 2", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel nástrojový 3", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel linkový 1", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel linkový 2", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel linkový 3", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel 220 V (3m/3p) 1", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel 220 V (3m/3p) 2", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel 220 V (5m/3p) 1", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel 220 V (5m/3p) 2", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel 220 V (5m/3p) 3", 26, 10);
INSERT INTO zkusebna_items (name,parent_id,price) VALUES ("Kabel 220 V (5m/5p) 1", 26, 10);
INSERT INTO zkusebna_items (name,category,price) VALUES ("Kontrabas", 'nastroje', 200);
INSERT INTO zkusebna_items (name,category,price) VALUES ("Basová kytara", 'nastroje', 200);
INSERT INTO zkusebna_items (name,category,price) VALUES ("Kytara", 'nastroje', 100);
INSERT INTO zkusebna_items (name,category,price) VALUES ("Cajón", 'nastroje', 200);
INSERT INTO zkusebna_items (name,category,price) VALUES ("Varhany", 'nastroje', 200);
