


CREATE TABLE `users`(
  `id` INT(10) NOT NULL AUTO_INCREMENT , 
  `name` VARCHAR(50) NOT NULL , 
  `email` VARCHAR(50) NOT NULL , 
  `password` VARCHAR(50) NOT NULL , 
  `user_type` VARCHAR(50) NOT NULL DEFAULT 'user' , PRIMARY KEY (`id`)
) ENGINE = InnoDB;



INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`) VALUES 
(NULL, 'admin', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', 'admin'),
(NULL, 'cashier', 'cashier@gmail.com', '84c8137f06fd53b0636e0818f3954cdb', 'user');



CREATE TABLE `items` ( `id` int(11) NOT NULL, `name` varchar(100) NOT NULL, `category_id` int(11) NOT NULL, `quantity` int(11) NOT NULL, `image` varchar(255) DEFAULT NULL, `medium_price` decimal(10,2) NOT NULL, `large_price` decimal(10,2) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `items` ADD PRIMARY KEY (`id`), ADD KEY `category_id` (`category_id`); ALTER TABLE `items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59 ; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `items`(`id`, `name`, `category_id`, `quantity`, `image`, `medium_price`, `large_price`) SELECT `id`, `name`, `category_id`, `quantity`, `image`, `medium_price`, `large_price` FROM `items`;



CREATE TABLE `categories` ( `id` int(11) NOT NULL, `name` varchar(255) NOT NULL, `image` varchar(255) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `categories` ADD PRIMARY KEY (`id`); ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16 ; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `categories`(`id`, `name`, `image`) SELECT `id`, `name`, `image` FROM `categories`;


ALTER TABLE `categories` ADD PRIMARY KEY (`id`); ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16 ; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `categories`(`id`, `name`, `image`) SELECT `id`, `name`, `image` FROM `categories`;


CREATE TABLE `daily_sales` ( `id` int(11) NOT NULL, `date` date NOT NULL, `total_sales` decimal(10,2) NOT NULL DEFAULT 0.00 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `daily_sales` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `date` (`date`); ALTER TABLE `daily_sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4 ; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `daily_sales`(`id`, `date`, `total_sales`) SELECT `id`, `date`, `total_sales` FROM `daily_sales`;


CREATE TABLE `employees` ( `id` int(11) NOT NULL, `name` varchar(100) NOT NULL, `email` varchar(100) NOT NULL, `password` varchar(255) NOT NULL, `birthdate` date NOT NULL, `age` int(11) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `employees` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `email` (`email`); ALTER TABLE `employees` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `employees`(`id`, `name`, `email`, `password`, `birthdate`, `age`) SELECT `id`, `name`, `email`, `password`, `birthdate`, `age` FROM `employees`;


CREATE TABLE `product_sales` ( `id` int(11) NOT NULL, `product_id` int(11) DEFAULT NULL, `quantity_sold` int(11) DEFAULT NULL, `date_sold` date DEFAULT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `product_sales` ADD PRIMARY KEY (`id`), ADD KEY `product_id` (`product_id`); ALTER TABLE `product_sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99 ; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `product_sales`(`id`, `product_id`, `quantity_sold`, `date_sold`) SELECT `id`, `product_id`, `quantity_sold`, `date_sold` FROM `product_sales`;

CREATE TABLE `transactions` ( `id` int(11) NOT NULL, `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(), `total_amount` decimal(10,2) NOT NULL, `order_details` text NOT NULL, `payment_status` varchar(20) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `transactions` ADD PRIMARY KEY (`id`); ALTER TABLE `transactions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9 ; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `transactions`(`id`, `transaction_date`, `total_amount`, `order_details`, `payment_status`) SELECT `id`, `transaction_date`, `total_amount`, `order_details`, `payment_status` FROM `transactions`;

CREATE TABLE `weekly_sales` ( `id` int(11) NOT NULL, `week_start_date` date NOT NULL, `total_sales` decimal(10,2) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; ALTER TABLE `weekly_sales` ADD PRIMARY KEY (`id`); ALTER TABLE `weekly_sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT; SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO `weekly_sales`(`id`, `week_start_date`, `total_sales`) SELECT `id`, `week_start_date`, `total_sales` FROM `weekly_sales`;



