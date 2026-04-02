CREATE TABLE order_items (
  id int NOT NULL AUTO_INCREMENT,
  order_id int DEFAULT NULL,
  product_name varchar(255) DEFAULT NULL,
  quantity int DEFAULT NULL,
  price_per_item decimal(10,2) DEFAULT NULL,
  subtotal decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY order_id (order_id),
  CONSTRAINT order_items_ibfk_1 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
);

CREATE TABLE orders (
  id int NOT NULL AUTO_INCREMENT,
  customer_name varchar(255) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  scout_name varchar(255) DEFAULT NULL,
  payment_mode varchar(50) DEFAULT NULL,
  total_amount decimal(10,2) DEFAULT NULL,
  order_date timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  status varchar(20) DEFAULT 'Pending',
  address varchar(255) DEFAULT NULL,
  comments varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE products (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(255) DEFAULT NULL,
  price decimal(10,2) DEFAULT NULL,
  image_url varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO products VALUES (1,'African Violet (4\")',10.00,'African_Violet.png'),(2,'Mum (4\")',10.00,'Mum.png'),(3,'Mum (6\")',20.00,'Mum.png'),(4,'Begonia (4\")',10.00,'Begonia.png'),(5,'Begonia (6\")',20.00,'Begonia.png'),(6,'Gerbera Daisy (4\")',12.00,'Daisy.png'),(7,'Gerbera Daisy (6\")',20.00,'Daisy.png'),(8,'Mini Rose (4\")',12.00,'Mini_Rose.png'),(9,'Mini Rose (6\")',20.00,'Mini_Rose.png'),(10,'Kalanchoe (6\")',18.00,'Kalanchoe.png'),(11,'Tulips (10 stems)',22.00,'Tulip.png'),(12,'Azalea (4\")',20.00,'Azalea.png'),(13,'Azalea (6\")',40.00,'Azalea.png'),(14,'Carnations (25 stems)',28.00,'Carnations.png'),(15,'Hydrangea (6\")',40.00,'Hydrangea.png'),(16,'Roses (25 stems)',90.00,'Rose.png');

