# database/replication

Source: https://bunny.net/docs/database/replication — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Replication

> Learn how Bunny Database separates storage from compute and replicates data across regions for low-latency access.

Bunny Database separates data storage (at rest) from data processing (in compute instances). This separation allows compute resources to be allocated dynamically in different regions while keeping data safely stored in its designated location.

Configure regions from the Dashboard or the [Bunny CLI](/docs/cli/quickstart):

<Tabs>
  <Tab title="Dashboard">
    Manage regions from **Dashboard > Edge Platform > Database > \[Select Database] > Regions**.
  </Tab>

  <Tab title="CLI">
    List, add, and remove regions with the `bunny db regions` commands:

    ```bash theme={null}
    bunny db regions list                 # show primary and replica regions
    bunny db regions add --primary DE      # add a primary region
    bunny db regions add --replicas UK,NY  # add replica regions
    bunny db regions remove --replicas UK  # remove a region
    ```

    At least one primary region must remain. See [`bunny db`](/docs/cli/commands/db) for the full command reference.
  </Tab>
</Tabs>

## Storage location

Data can be stored at rest in:

* Toronto, CA (North America)
* Frankfurt, DE (Europe)

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/replication/storage-location.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=9ceed285d6694f4b7288ca3ab13e7f0c" alt="Storage Location" width="1058" height="306" data-path="images/database/replication/storage-location.png" />
</Frame>

If a database doesn't receive any requests, it will be moved to object storage and removed from all active regions to optimize costs.

## Enabled regions

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/replication/enabled-regions.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=cbfee16e3bbafe4e3743c5823fdd8f46" alt="Enabled Regions" width="1842" height="1450" data-path="images/database/replication/enabled-regions.png" />
</Frame>

### Replication regions

Replication regions function as dynamically provisioned read replicas, offering fast, low-latency data access while proxying write operations to the primary region.

### Primary regions

The primary region handles write operations. You can select multiple regions, but only one is active at a time. The active primary region is automatically chosen based on latency. If a database becomes idle, the primary region may change when it's reactivated.
