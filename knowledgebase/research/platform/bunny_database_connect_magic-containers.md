# database/connect/magic-containers

Source: https://bunny.net/docs/database/connect/magic-containers — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Bunny Magic Containers

> Connect your Magic Container apps to Bunny Database using environment variables

You can connect [Magic Container](/docs/magic-containers) apps to your database by adding credentials as environment variables directly from the database dashboard.

<Steps>
  <Step title="Go to the Access page">
    Navigate to **Dashboard > Edge Platform > Database > \[Select Database] > Access**.
  </Step>

  <Step title="Generate tokens">
    Click **Generate Tokens** to create new access credentials for your database.

    <Frame>
      <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/connect/generate-tokens.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=0f93492b4c9e0c7a9a688aa6e61f423b" alt="Generate Tokens" width="770" height="508" data-path="images/database/connect/generate-tokens.png" />
    </Frame>
  </Step>

  <Step title="Add to Magic Container">
    Once the tokens are generated, click **Add Secrets to Magic Container App**.

    <Frame>
      <img
        src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/connect/magic-containers/add-secrets-to-magic-container-app.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=320e97e29d0dcfe47adb064978990574"
        alt="Add to Magic
Container"
        width="900"
        height="538"
        data-path="images/database/connect/magic-containers/add-secrets-to-magic-container-app.png"
      />
    </Frame>
  </Step>

  <Step title="Select your app">
    Choose the Magic Container app you want to connect to your database.

    <Frame>
      <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/database/connect/magic-containers/select-app.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=ae103bd1881cf0f12261ee4ca0fd494c" alt="Select App" width="876" height="666" data-path="images/database/connect/magic-containers/select-app.png" />
    </Frame>
  </Step>

  <Step title="Access the environment variables">
    The database URL and access token are now available as environment variables in your app. Use them to connect to your database:

    ```typescript theme={null}
    import { createClient } from "@libsql/client/web";

    const client = createClient({
      url: process.env.BUNNY_DATABASE_URL,
      authToken: process.env.BUNNY_DATABASE_AUTH_TOKEN,
    });

    const result = await client.execute("SELECT * FROM users");
    ```
  </Step>
</Steps>
