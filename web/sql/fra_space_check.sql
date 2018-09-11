WITH fra_space
AS
(
  SELECT
    name
  , space_limit
  , space_limit - space_used + space_reclaimable space_remaining
  , 100 - ROUND(((space_limit - space_used + space_reclaimable)/space_limit)*100) space_used_pct
  FROM
    v$recovery_file_dest
)
SELECT
    name
  , ROUND(space_limit/(1024*1024*1024),3) space_limit_gb
  , ROUND(space_remaining/(1024*1024*1024),3) space_remaining_gb 
  , space_used_pct
  , CASE 
      WHEN space_used_pct > 80 THEN 'CRITICAL'
      WHEN space_used_pct > 60 THEN 'WARNING'
      ELSE 'OK'
    END status
FROM
  fra_space
