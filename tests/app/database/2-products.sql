CREATE TABLE
    products (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(255) NOT NULL,
        qty INT(6) NOT NULL,
        type VARCHAR(255) NOT NULL,
        categoryId INT,
        createdAt DATETIME,
        updatedAt DATETIME,
        deletedAt DATETIME,
        PRIMARY KEY (id)
    );