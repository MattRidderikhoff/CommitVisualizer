### Spin up the site:
1. cd into DashboardCreator
2. Enter the following on the command line:
    <code>php bin/console server:run</code> 
3. In any browser go to "localhost:8000"

### API Authentication:
1. follow instructions here: https://help.github.com/articles/creating-a-personal-access-token-for-the-command-line/
2. take your generated token, and paste it into /public/github_auth_token.txt (do NOT commit your changes, as it will delete your token and you will need to make a new one)