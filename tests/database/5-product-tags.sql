CREATE TABLE
    product_tags (
        id INT AUTO_INCREMENT NOT NULL,
        productId INT NOT NULL,
        tagId INT NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_products_product_tags (productId),
        INDEX idx_tags_product_tags (tagId)
    );

ALTER TABLE product_tags
ADD
    CONSTRAINT fk_products_product_tags FOREIGN KEY (productId) REFERENCES products(id);

ALTER TABLE product_tags
ADD
    CONSTRAINT fk_tags_product_tags FOREIGN KEY (tagId) REFERENCES tags(id);