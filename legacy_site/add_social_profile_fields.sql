-- Add bio and location to users table for social profiles
ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN location VARCHAR(100) DEFAULT NULL;
