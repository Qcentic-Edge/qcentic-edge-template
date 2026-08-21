# database/quickstart

Source: https://bunny.net/docs/database/quickstart — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Quickstart

> Create a database and run your first query in minutes

<Steps>
  <Step title="Create a database">
    <Tabs>
      <Tab title="Dashboard">
        1. From your Bunny dashboard, click **Add** in the left-hand sidebar and select **Database**.

                   <Frame>
                     <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/quickstart/add-new-database.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=2bbcb49fa2e85b7ff39a27e7fb21c304" alt="Add Database" width="1396" height="1102" data-path="images/database/quickstart/add-new-database.png" />
                   </Frame>

        2. Enter a unique name for your database. This name will be used to identify your database in the dashboard and connection URLs.

                   <Frame>
                     <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/quickstart/database-name.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=d21c31d61f103bf365fbe0661bf492f9" alt="Database Name" width="1230" height="344" data-path="images/database/quickstart/database-name.png" />
                   </Frame>

        3. Choose how you want your database deployed:

           * **Automatic**: We will choose the optimal regions for your database based on your location and best performance.
           * **Single region**: Deploy your database to a single region without any replication. Best for development or applications with users in one geographic area.
           * **Manual**: Manually select your storage location along with primary and replication regions. Best for applications with specific latency or compliance requirements.

                   <Frame>
                     <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/quickstart/region-selection.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=ba40e0acd32dc6d111d8ebc901f67ed3" alt="Region Selection" width="1604" height="800" data-path="images/database/quickstart/region-selection.png" />
                   </Frame>

        4. Click **Add Database** to create your database.

                   <Frame>
                     <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/quickstart/add-database-button.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=d2fc6519b8229be2c4720bead1d99ad2" alt="Add Database Button" width="510" height="258" data-path="images/database/quickstart/add-database-button.png" />
                   </Frame>

        5. Once created, copy your connection credentials:

           * **Database URL**: Your database endpoint
           * **Access Token**: Choose between **Full Access** (read/write) or **Read Only** depending on your needs

                   <Frame>
                     <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/quickstart/database-credentials.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=8f281ec47b60a48f94720cde79398ab0" alt="Database Credentials" width="1590" height="1038" data-path="images/database/quickstart/database-credentials.png" />
                   </Frame>

                   <Warning>
                     Keep your access tokens secure. Never commit them to version control or expose
                     them in client-side code.
                   </Warning>

        6. **Optional**: if you have an existing Edge Script or Magic Container, you can add your database credentials directly from this page:

           1. Click **Add Secrets to Edge Script** or **Add Secrets to Magic Container Apps**
           2. Select the script or app you want to connect
           3. The database URL and access token will be added as environment variables automatically

                   <Frame>
                     <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/quickstart/add-database-secrets-to-scripts-or-apps.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=af70f3f372731300f67a9ded3a1174f2" alt="Add Secrets" width="1946" height="966" data-path="images/database/quickstart/add-database-secrets-to-scripts-or-apps.png" />
                   </Frame>
      </Tab>

      <Tab title="CLI">
        <Note>
          Don't have the CLI installed? See the [CLI quickstart](/docs/cli/quickstart) to install and authenticate.
        </Note>

        Create a database interactively:

        ```bash theme={null}
        bunny db create
        ```

        You'll be prompted for a name and region mode (automatic, single region, or manual). After creation, the CLI offers to:

        * **Link** the current directory to the database (writes `.bunny/database.json`)
        * **Generate** a full-access auth token
        * **Save** `BUNNY_DATABASE_URL` and `BUNNY_DATABASE_AUTH_TOKEN` to a `.env` file

        For a fully non-interactive run (e.g. in CI):

        ```bash theme={null}
        bunny db create --name mydb --primary FR
        ```

        <Warning>
          Keep your access tokens secure. Never commit them to version control or
          expose them in client-side code.
        </Warning>

        See [`bunny db`](/docs/cli/commands/db) for the full command reference, including `bunny db shell` for an interactive SQL prompt.
      </Tab>
    </Tabs>
  </Step>

  <Step title="Install an SDK and connect">
    Install the official SDK for your language and connect to your database:

    <Tabs>
      <Tab title="TypeScript">
        Install the libSQL client:

        ```bash theme={null}
        npm install @libsql/client
        ```

        Connect and run queries:

        ```typescript theme={null}
        import { createClient } from "@libsql/client/web";

        const client = createClient({
          url: "libsql://your-database-id.lite.bunnydb.net",
          authToken: "your-access-token",
        });

        // Create a table
        await client.execute(`
          CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL
          )
        `);

        // Insert data
        await client.execute({
          sql: "INSERT INTO users (name, email) VALUES (?, ?)",
          args: ["Kit Hopkins", "kit@bunny.net"],
        });

        // Query data
        const result = await client.execute("SELECT * FROM users");
        console.log(result.rows);
        ```
      </Tab>

      <Tab title="Rust">
        Add the libSQL crate to your `Cargo.toml`:

        ```toml theme={null}
        [dependencies]
        libsql = "0.6"
        tokio = { version = "1", features = ["full"] }
        ```

        Connect and run queries:

        ```rust theme={null}
        use libsql::Builder;

        #[tokio::main]
        async fn main() -> Result<(), libsql::Error> {
            let db = Builder::new_remote(
                "libsql://your-database-id.lite.bunnydb.net".to_string(),
                "your-access-token".to_string(),
            )
            .build()
            .await?;

            let conn = db.connect()?;

            // Create a table
            conn.execute(
                "CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL
                )",
                (),
            )
            .await?;

            // Insert data
            conn.execute(
                "INSERT INTO users (name, email) VALUES (?1, ?2)",
                ("Kit Hopkins", "kit@bunny.net"),
            )
            .await?;

            // Query data
            let mut rows = conn.query("SELECT * FROM users", ()).await?;
            while let Some(row) = rows.next().await? {
                let id: i64 = row.get(0)?;
                let name: String = row.get(1)?;
                let email: String = row.get(2)?;
                println!("User: {} - {} ({})", id, name, email);
            }

            Ok(())
        }
        ```
      </Tab>

      <Tab title="Go">
        Install the libSQL driver:

        ```bash theme={null}
        go get github.com/tursodatabase/libsql-client-go/libsql
        ```

        Connect and run queries:

        ```go theme={null}
        package main

        import (
            "database/sql"
            "fmt"
            "log"

            _ "github.com/tursodatabase/libsql-client-go/libsql"
        )

        func main() {
            url := "libsql://your-database-id.lite.bunnydb.net?authToken=your-access-token"

            db, err := sql.Open("libsql", url)
            if err != nil {
                log.Fatal(err)
            }
            defer db.Close()

            // Create a table
            _, err = db.Exec(`
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL
                )
            `)
            if err != nil {
                log.Fatal(err)
            }

            // Insert data
            _, err = db.Exec(
                "INSERT INTO users (name, email) VALUES (?, ?)",
                "Kit Hopkins", "kit@bunny.net",
            )
            if err != nil {
                log.Fatal(err)
            }

            // Query data
            rows, err := db.Query("SELECT * FROM users")
            if err != nil {
                log.Fatal(err)
            }
            defer rows.Close()

            for rows.Next() {
                var id int64
                var name, email string
                rows.Scan(&id, &name, &email)
                fmt.Printf("User: %d - %s (%s)\n", id, name, email)
            }
        }
        ```
      </Tab>

      <Tab title=".NET">
        Install the libSQL client package:

        ```bash theme={null}
        dotnet add package Libsql.Client
        ```

        Connect and run queries:

        ```csharp theme={null}
        using Libsql.Client;

        var client = DatabaseClient.Create(opts =>
        {
            opts.Url = "libsql://your-database-id.lite.bunnydb.net";
            opts.AuthToken = "your-access-token";
        });

        // Create a table
        await client.Execute(@"
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL
            )
        ");

        // Insert data
        await client.Execute(
            "INSERT INTO users (name, email) VALUES (?, ?)",
            "Kit Hopkins", "kit@bunny.net"
        );

        // Query data
        var result = await client.Execute("SELECT * FROM users");
        foreach (var row in result.Rows)
        {
            Console.WriteLine($"User: {row["id"]} - {row["name"]} ({row["email"]})");
        }
        ```
      </Tab>
    </Tabs>
  </Step>
</Steps>
