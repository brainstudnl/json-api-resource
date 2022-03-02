# Contributing
1. Update the code
2. Update the tag: `git tag v1.0.x`
3. Push the new tag `git push origin v1.0.x`
4. Publish the new version: `curl --data tag=v1.0.x "https://<DEPLOY TOKEN SEE 1PASSWORD>:<YOUR API TOKEN>@gitlab.com/api/v4/projects/28242271/packages/composer"`
5. Check the [packages](https://gitlab.com/brainstud/packages/json-api/-/packages) page if it was successful
6. Update the package in your project with composer
