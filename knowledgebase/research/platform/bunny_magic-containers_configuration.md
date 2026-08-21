# magic-containers/configuration

Source: https://bunny.net/docs/magic-containers/configuration — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Configuration

> Configure your container's environment variables, health checks, endpoints, and runtime settings.

Once you have a [deployed container](/docs/magic-containers/quickstart), you can modify its configuration by going to **Magic Containers**, selecting your container, then clicking **Container Settings**.

## General settings

Click **Edit** to modify environment variables, health checks, endpoints, and resource allocation.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/L57MhyfuZyBVCcmn/images/docs/7e7018b9b95eae9ed2157305c047f802618299c40888dd8202562ecc5392be2c-image.png?fit=max&auto=format&n=L57MhyfuZyBVCcmn&q=85&s=3c19028401ce945771bccfc9e003e5ff" alt="" width="2082" height="1550" data-path="images/docs/7e7018b9b95eae9ed2157305c047f802618299c40888dd8202562ecc5392be2c-image.png" />
</Frame>

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/L57MhyfuZyBVCcmn/images/docs/b3926acc2c98c4d616bed5973d9cbdb3d442f0a7d9253457cfcdca0ff2eb36b3-image.png?fit=max&auto=format&n=L57MhyfuZyBVCcmn&q=85&s=1a8580d68d78b94ede01b321937be041" alt="" width="2330" height="1752" data-path="images/docs/b3926acc2c98c4d616bed5973d9cbdb3d442f0a7d9253457cfcdca0ff2eb36b3-image.png" />
</Frame>

After making changes, click **Update Container** to apply the new configuration.

## Advanced settings

Click the **Advanced** tab to configure runtime behavior.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/L57MhyfuZyBVCcmn/images/docs/8e2a2cdaef5760ac80ed1d5cf7bb261992e8748211229bb8d64119714b55b3df-image.png?fit=max&auto=format&n=L57MhyfuZyBVCcmn&q=85&s=baef5d6191a64900eecae5daf842a83f" alt="" width="1882" height="1618" data-path="images/docs/8e2a2cdaef5760ac80ed1d5cf7bb261992e8748211229bb8d64119714b55b3df-image.png" />
</Frame>

The following settings are available:

* **Startup command**: A custom command that executes when the container is launched, giving you control over the initial behavior.
* **Container Arguments**: Arguments added to the container's entry point when starting the image. Useful for passing runtime parameters.
* **Working Dir**: The working directory for the container runtime. Configure this for applications that rely on specific file paths.
