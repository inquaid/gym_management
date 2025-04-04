-- Check if user_id column exists in payments table
SET @dbname = 'gym_mng';
SET @tablename = 'payments';
SET @columnname = 'user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 'Column exists already'",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN `user_id` INT(11) NOT NULL AFTER `order_id`, ADD FOREIGN KEY (`user_id`) REFERENCES `members`(`user_id`) ON DELETE CASCADE")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists; 