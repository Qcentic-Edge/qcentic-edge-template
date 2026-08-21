# magic-containers/environment-variables

Source: https://bunny.net/docs/magic-containers/environment-variables — fetched 2026-08-21

> ## Documentation Index
> Fetch the complete documentation index at: https://bunny.net/docs/llms.txt
> Use this file to discover all available pages before exploring further.

# Environment Variables

> Configure your container's runtime settings using environment variables.

Environment variables allow you to provide dynamic configuration options to your container without hardcoding values in your code.

## Automatic detection

When you select a registry image, Magic Containers scans the image metadata to identify environment variables the container might use. If detected, the variables are displayed with their names and any default values.

<Note>
  Automatic scanning is currently only supported for public Docker Hub images.
</Note>

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/1dd222658a76584eb0d68791cd8042c9e7ae84652dedfd055a55190c39567152-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=d7c8902ced9239d27f3e3af90f8b8841" alt="" width="2294" height="794" data-path="images/docs/1dd222658a76584eb0d68791cd8042c9e7ae84652dedfd055a55190c39567152-image.png" />
</Frame>

Click **Go To Environment Variables** to add all detected variables with a single click.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/068bfa03c12e199dc92d7b824981751105cb62c4a2c19f3e322991d4f96bcf30-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=adea3a59b19863c3845615d2a1fcb52d" alt="" width="2308" height="1244" data-path="images/docs/068bfa03c12e199dc92d7b824981751105cb62c4a2c19f3e322991d4f96bcf30-image.png" />
</Frame>

## Manual configuration

If no variables are detected, or you need to add additional ones, you can configure them manually:

1. Go to **Magic Containers** and select your container.

2. Click **Container Settings**, then **Edit**.

3. Select the **Environment Variables** tab and click **+ Add New Variable**.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/kcU5tb-Sz5318NF5/images/docs/f2c12c131ee6d540589efa9d281c2fc585bdf11f43bef60b7dca0d2cd31548ad-image.png?fit=max&auto=format&n=kcU5tb-Sz5318NF5&q=85&s=2a001869a20edf3c2666ca687c7461d5" alt="" width="1874" height="1012" data-path="images/docs/f2c12c131ee6d540589efa9d281c2fc585bdf11f43bef60b7dca0d2cd31548ad-image.png" />
</Frame>

4. Enter the variable name and value, then click **Update Container**.

<Frame>
  <img src="https://mintcdn.com/bunnynet-cb9733c2/GYo51EPI6zuebYhU/images/docs/2d09d6db1ba38fb27ffd441c088d345d2e342a09fa7ba7d33ab4e05848a7b3b7-image.png?fit=max&auto=format&n=GYo51EPI6zuebYhU&q=85&s=10f9dd734a64d0b7624cf49053f16c36" alt="" width="2330" height="732" data-path="images/docs/2d09d6db1ba38fb27ffd441c088d345d2e342a09fa7ba7d33ab4e05848a7b3b7-image.png" />
</Frame>
