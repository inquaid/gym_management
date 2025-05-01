-- ========== MEMBERSHIP SYSTEM ==========

-- Membership Plans Table
CREATE TABLE membership_plan (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name ENUM('Basic', 'Standard', 'Premium', 'VIP') NOT NULL,
  price INT(11) NOT NULL,  -- Monthly fee (in Taka)
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*
-- Admin Table
CREATE TABLE admin (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(50) NOT NULL,
  name VARCHAR(50) NOT NULL,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/
-- Members Table (Updated with Due Amount)
CREATE TABLE members (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  fullname VARCHAR(50) NOT NULL,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(100) NOT NULL,
  gender VARCHAR(20) NOT NULL,
  reg_date DATE NOT NULL,
  plan_id INT(11) NOT NULL,  -- References membership_plan table
  due_amount INT(11) NOT NULL,  -- Remaining amount to be paid for the month
  address VARCHAR(100) NOT NULL,
  contact VARCHAR(15) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Active',
  attendance_count INT(11) NOT NULL DEFAULT 0,
  ini_weight INT(11) NOT NULL DEFAULT 0,
  curr_weight INT(11) NOT NULL DEFAULT 0,
  progress_date DATE NOT NULL,
  reminder INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id),
  FOREIGN KEY (plan_id) REFERENCES membership_plan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Attendance Table
CREATE TABLE attendance (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  check_in_time DATETIME NOT NULL,
  check_out_time DATETIME NULL,
  present TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES members(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- ========== EQUIPMENT & STAFF ==========

-- Equipment Table
CREATE TABLE equipment (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  amount INT(11) NOT NULL,
  quantity INT(11) NOT NULL,
  vendor VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  address VARCHAR(100) NOT NULL,
  contact VARCHAR(15) NOT NULL,
  purchase_date DATE NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Staffs Table
CREATE TABLE staffs (
  user_id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(50) NOT NULL,
  email VARCHAR(50) NOT NULL,
  fullname VARCHAR(50) NOT NULL,
  address VARCHAR(100) NOT NULL,
  designation VARCHAR(50) NOT NULL,
  gender VARCHAR(10) NOT NULL,
  contact VARCHAR(15) NOT NULL,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Announcements Table
CREATE TABLE announcements (
  id INT(11) NOT NULL AUTO_INCREMENT,
  message VARCHAR(255) NOT NULL,
  date DATE NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Reminder Table
CREATE TABLE reminder (
  id INT(50) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  name VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('Pending', 'Completed') NOT NULL DEFAULT 'Pending',
  date DATETIME NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES members(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*
-- To-Do List Table
CREATE TABLE todo (
  id INT(11) NOT NULL AUTO_INCREMENT,
  task_status VARCHAR(50) NOT NULL,
  task_desc VARCHAR(255) NOT NULL,
  user_id INT(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES staffs(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
*/

-- ========== SHOP SECTION ==========

-- Products Table
CREATE TABLE products (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock_quantity INT(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Orders Table
CREATE TABLE orders (
  order_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,  -- References members table
  order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('Pending', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (order_id),
  FOREIGN KEY (user_id) REFERENCES members(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Order Items Table
CREATE TABLE order_items (
  item_id INT(11) NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,  -- References orders table
  product_id INT(11) NOT NULL,  -- References products table
  quantity INT(11) NOT NULL,
  price DECIMAL(10,2) NOT NULL,  -- Stores the price at purchase time
  PRIMARY KEY (item_id),
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- Payments Table (Supports Partial Payments)
CREATE TABLE payments (
  payment_id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,  -- References members table
  order_id INT(11) NULL,  -- Can be NULL for membership payments
  payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  amount DECIMAL(10,2) NOT NULL,  -- Allows partial payments
  payment_method VARCHAR(20) NOT NULL DEFAULT 'Cash',
  status ENUM('Success', 'Failed', 'Pending') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (payment_id),
  FOREIGN KEY (user_id) REFERENCES members(user_id) ON DELETE CASCADE,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;