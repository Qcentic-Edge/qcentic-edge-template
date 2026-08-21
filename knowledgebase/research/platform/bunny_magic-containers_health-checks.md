# magic-containers/health-checks

Source: https://bunny.net/docs/magic-containers/health-checks — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Health Checks

> Configure health checks to ensure your containerized applications are reliable and available.

Health checks verify that your application is functioning correctly and ready to handle incoming requests. By checking startup readiness and ongoing liveness, Magic Containers can prevent issues before they impact your users.

## Health check types

Magic Containers offers three types of health checks:

* **Startup** - Verifies the application has successfully started. No requests are routed until this check passes.
* **Readiness** - Ensures the application is ready to handle incoming requests. We strongly recommend enabling this check to avoid failed requests.
* **Liveness** - Confirms the application is actively running without problems.

## Configuration

<Steps>
  <Step title="Open container settings">
    Navigate to **Magic Containers**, select your app, click **Container Settings**, then **Edit**.
  </Step>

  <Step title="Go to Monitoring tab">
    In the Container Settings menu, select the **Monitoring** tab.

    <Frame>
      <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/181b5d78e5a4dbd5b657edb2036dce80d603f353d3c4007310e248d0f182554b-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=40bea0de9f1fc4f6f78ea392450c2118" alt="" width="2330" height="1554" data-path="images/docs/181b5d78e5a4dbd5b657edb2036dce80d603f353d3c4007310e248d0f182554b-image.png" />
    </Frame>
  </Step>

  <Step title="Enable health checks">
    Click the checkbox for each health check type you want to enable (**Startup**, **Readiness**, **Liveness**).
  </Step>

  <Step title="Configure the check method">
    Select either **HTTP GET** or **TCP** from the dropdown:

    **HTTP GET** - Provide the path and port for the HTTP request.

    <Frame>
      <img src="https://mintcdn.com/bunnynet-cb9733c2/kcU5tb-Sz5318NF5/images/docs/d06706534176fda4c5f4d451b60b0def84a4cb76e082def6e2eda5cd7c632410-image.png?fit=max&auto=format&n=kcU5tb-Sz5318NF5&q=85&s=17f0bc8e7e2912f779ed14f29f9c5b78" alt="" width="1178" height="1086" data-path="images/docs/d06706534176fda4c5f4d451b60b0def84a4cb76e082def6e2eda5cd7c632410-image.png" />
    </Frame>

    **TCP** - Specify the port for the TCP health check.

    <Frame>
      <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/53acc10a3421d0d09e1f494fc6e5c8faa4dcc7a43d243a0b570bec022bb61349-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=9957d678c92010d0483d9eac44a4a5c7" alt="" width="1158" height="956" data-path="images/docs/53acc10a3421d0d09e1f494fc6e5c8faa4dcc7a43d243a0b570bec022bb61349-image.png" />
    </Frame>
  </Step>
</Steps>

<Info>
  We strongly recommend enabling at least the Readiness health check to minimize failed requests and ensure a smoother user experience.
</Info>
