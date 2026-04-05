-- After OAuth login, existing image rows often still have user_id NULL.
-- The app only lists images WHERE user_id = logged-in user, so those rows are hidden.
-- Run ONE of the options below (adjust email if needed), then refresh ImageKpr.

-- Option A: Assign every legacy image (NULL user_id) to a specific account by email:
-- UPDATE images i
-- JOIN users u ON u.email = 'googooboyy@gmail.com'
-- SET i.user_id = u.id
-- WHERE i.user_id IS NULL;

-- Option B: Single-user site — give all NULL rows to the first user row:
-- UPDATE images SET user_id = (SELECT id FROM users ORDER BY id ASC LIMIT 1) WHERE user_id IS NULL;
