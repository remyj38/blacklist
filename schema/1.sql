CREATE TABLE `entries` (
	`id`                              INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`ip`		TEXT NOT NULL UNIQUE,
	`creation_date`	INTEGER NOT NULL,
	`expiration_date`	INTEGER,
	`creator`                    	TEXT,
	`updator`		TEXT
);
CREATE TABLE `users` (
	`id`                              INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	`username`	INTEGER NOT NULL UNIQUE,
	`password`	INTEGER NOT NULL,
	`otpSecret`	TEXT,
	`isAdmin`                    INTEGER DEFAULT 0
);
