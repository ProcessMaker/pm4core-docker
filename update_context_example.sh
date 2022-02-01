# Sets an example GITHUB_CONTEXT for local testing
# Edit pr_body_example.txt to set the PR's body
# run with source ./update_context_example.sh
export GITHUB_CONTEXT=$(cat github_context_example.json | jq -r ".body |= \"$(cat pr_body_example.txt)\"")
