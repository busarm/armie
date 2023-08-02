CREATE TABLE
    categories (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description VARCHAR(255) DEFAULT NUll,
        createdAt DATETIME,
        updatedAt DATETIME,
        PRIMARY KEY (id)
    );