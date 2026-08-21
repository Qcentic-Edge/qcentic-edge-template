# magic-containers/endpoints

Source: https://bunny.net/docs/magic-containers/endpoints — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Endpoints

> Expose your container to the internet using CDN or Anycast endpoints.

Once you have a [deployed container](/docs/magic-containers/quickstart), you can expose it to the internet using two endpoint types: **CDN** for HTTP(S) traffic or **Anycast** for direct IP access.

## CDN

CDN endpoints route HTTP(S) traffic through bunny.net's edge network, improving performance and reducing latency based on user location.

<Info>
  For more information about CDN, see our [CDN
  documentation](https://bunny.net/academy/cdn/what-is-a-cdn-content-delivery-network/).
</Info>

To create a CDN endpoint:

1. Go to **Magic Containers** and select your container.

2. Click **Endpoints**, then **Add New Endpoint**.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/1ef14f4c14be1d252dfecd23dac3a6d6d81394af6f5ee821323f7c8e78203ad2-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=4327528a597dd3af0fa3f175f7c410db" alt="" width="2418" height="1340" data-path="images/docs/1ef14f4c14be1d252dfecd23dac3a6d6d81394af6f5ee821323f7c8e78203ad2-image.png" />
</Frame>

3. Select **CDN** as the type.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/kcU5tb-Sz5318NF5/images/docs/f0373c65fac9c4ee93c2a5bc2450ab4e31b921e1f89317d7d782099918044a65-image.png?fit=max&auto=format&n=kcU5tb-Sz5318NF5&q=85&s=18ec94f126c369bd19ddf623d59e29cd" alt="" width="1366" height="1758" data-path="images/docs/f0373c65fac9c4ee93c2a5bc2450ab4e31b921e1f89317d7d782099918044a65-image.png" />
</Frame>

4. Configure the endpoint:
   * **Name**: A unique name for this endpoint.
   * **Container Port**: The port your application listens on.
   * **SSL for origin**: Enable if your application uses SSL internally.

<Info>
  It's recommended to run one process per container. If running multiple
  processes, ensure they use different ports.
</Info>

5. Click **Add Endpoint**.

### Sticky sessions

Sticky sessions ensure all requests from a client are routed to the same server instance, maintaining session state across requests.

To enable sticky sessions:

1. In the endpoint configuration, select **Sticky Session**.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/L57MhyfuZyBVCcmn/images/docs/81299b7686d70d6ddb86f019bfcfe9ab80f97bc154949c8ac1cfd1dac4563f1f-image.png?fit=max&auto=format&n=L57MhyfuZyBVCcmn&q=85&s=e1d26db2e7ed7da30c5511a0d1ee66c1" alt="" width="1348" height="1052" data-path="images/docs/81299b7686d70d6ddb86f019bfcfe9ab80f97bc154949c8ac1cfd1dac4563f1f-image.png" />
</Frame>

2. Choose an identifier (headers like `X-Forwarded-For` or `User-Agent`, or cookies like `SessionID`).

3. Click **Add Endpoint**.

## Anycast

Anycast endpoints map your container to an Anycast IP address, routing requests to the nearest node for improved performance.

To create an Anycast endpoint:

1. Go to **Magic Containers** and select your container.

2. Click **Endpoints**, then **Add New Endpoint**.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/08f7158fa6535905e68b635aca2e223d1818ab25753596466e881f51bbea8ee0-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=df74487c6793ff9ba57f99ad39eb5090" alt="" width="2418" height="1340" data-path="images/docs/08f7158fa6535905e68b635aca2e223d1818ab25753596466e881f51bbea8ee0-image.png" />
</Frame>

3. Select **Anycast** as the type.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/471a57e384cb41e7cbf6373ad1352046d6307207e105b0c0fe0b710096023f92-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=e5f010587f575b15008f50b961b5afaf" alt="" width="1348" height="1700" data-path="images/docs/471a57e384cb41e7cbf6373ad1352046d6307207e105b0c0fe0b710096023f92-image.png" />
</Frame>

4. Configure the endpoint:
   * **Name**: A unique name for this endpoint.
   * **Container Port**: The port your application listens on inside the container.
   * **Exposed Port**: The port available on the Anycast IP.

5. Click **Add Endpoint**.
