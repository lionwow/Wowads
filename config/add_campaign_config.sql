-- Add config column to campaigns table
ALTER TABLE campaigns ADD COLUMN config JSON AFTER end_date;
