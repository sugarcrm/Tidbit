INSERT INTO user_preferences
    (
        id,
        category,
        date_entered,
        date_modified,
        assigned_user_id,
        contents
    )
SELECT
    MD5(id),
    'global',
    NOW() AS date_entered,
    NOW() AS date_modified,
    id,
    'YTo0OntzOjg6InRpbWV6b25lIjtzOjE1OiJBbWVyaWNhL1Bob2VuaXgiO3M6MjoidXQiO2k6MTtzOjI0OiJIb21lX1RFQU1OT1RJQ0VfT1JERVJfQlkiO3M6MTA6ImRhdGVfc3RhcnQiO3M6MTI6InVzZXJQcml2R3VpZCI7czozNjoiYTQ4MzYyMTEtZWU4OS0wNzE0LWE0YTItNDY2OTg3YzI4NGY0Ijt9'
FROM
    users
WHERE
    id LIKE 'seed-Users%' and id NOT IN (select assigned_user_id from user_preferences) 
    
    
