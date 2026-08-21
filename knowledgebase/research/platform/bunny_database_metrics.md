# database/metrics

Source: https://bunny.net/docs/database/metrics — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Metrics

> Monitor your database performance with real-time metrics and usage statistics

Bunny Database provides detailed metrics to help you monitor performance, track usage, and optimize your queries. Access metrics from **Dashboard > Edge Platform > Database > \[Select Database] > Metrics**.

<Tip>
  Prefer the terminal? The [Bunny CLI](/docs/cli/quickstart) reports usage statistics
  for a database with `bunny db usage`:

  ```bash theme={null}
  bunny db usage                             # current month
  bunny db usage --period 7d                 # last 7 days
  bunny db usage --from 2026-01-01 --to 2026-01-31
  ```

  It shows rows read and written, query count, average latency, and storage. See
  [`bunny db`](/docs/cli/commands/db) for the full command reference.
</Tip>

## Date range

Use the date picker to filter all metrics by a specific time period. Select from preset ranges or define a custom date range to analyze historical performance.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/metrics/date-range-picker.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=b93432cad4db78ab1c2b2674c1b7b29a" alt="Date Range Picker" width="1650" height="1020" data-path="images/database/metrics/date-range-picker.png" />
</Frame>

## Available metrics

### Rows read and written

Track the number of database rows read and written over time. This metric helps you understand your database workload and identify usage patterns.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/metrics/rows-read-written.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=c239ee5ab2e13cc07ce99c5e8f79a221" alt="Rows Read and Written" width="1944" height="1026" data-path="images/database/metrics/rows-read-written.png" />
</Frame>

| Metric       | Description                                                              |
| ------------ | ------------------------------------------------------------------------ |
| Rows Read    | Total number of rows retrieved by `SELECT` queries                       |
| Rows Written | Total number of rows affected by `INSERT`, `UPDATE`, `DELETE` operations |

High read counts may indicate opportunities for caching or query optimization. Sudden spikes in writes may indicate bulk operations or potential issues worth investigating.

### Latency

Monitor query response times across different percentiles to understand your database performance characteristics.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/metrics/latency.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=fbe715465ae7a334e74431ba1309bf96" alt="Latency Metrics" width="1940" height="1020" data-path="images/database/metrics/latency.png" />
</Frame>

| Metric  | Description                                                             |
| ------- | ----------------------------------------------------------------------- |
| Average | Mean response time across all queries                                   |
| P75     | 75th percentile latency (75% of queries complete faster than this time) |
| P95     | 95th percentile latency (95% of queries complete faster than this time) |

Average latency should remain consistent over time. Increases may indicate growing data volumes or query complexity. A large gap between P95 and average suggests some queries need optimization. Consider adding indexes or restructuring slow queries.

### Query count

View the total number of queries executed against your database over time. Correlate query volume with application traffic to ensure expected behavior and identify unusual patterns.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/metrics/query-count.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=96917eda5687fc46c91e01ea2d986f0c" alt="Query Count" width="1944" height="1018" data-path="images/database/metrics/query-count.png" />
</Frame>

### Database size

Monitor your database storage consumption over time. This metric shows the total size of your database including all tables, indexes, and metadata. Track growth trends to plan for scaling and identify unexpected increases.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/metrics/database-size.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=393b047a5691199a43447ad9812e589f" alt="Database Size" width="1946" height="1022" data-path="images/database/metrics/database-size.png" />
</Frame>
