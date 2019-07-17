WITH process_counts
AS
(
  SELECT
    value max_process_count,
    (SELECT COUNT(1) FROM v$process) current_process_count
  FROM
    v$parameter2
  WHERE
    name = 'processes'
),
process_counts_calc
AS(
  SELECT
    pc.*,
    (current_process_count/max_process_count)*100 percentage_of_max
  FROM
    process_counts pc
)
SELECT
  pcc.*,
  CASE
    WHEN percentage_of_max > 80 THEN 'CRITICAL'
    WHEN percentage_of_max > 70 THEN 'WARNING'
    ELSE 'OK'

  END status
FROM
  process_counts_calc pcc
