ALTER TABLE products
ADD
    CONSTRAINT fk_product_category FOREIGN KEY (categoryId) REFERENCES categories(id);