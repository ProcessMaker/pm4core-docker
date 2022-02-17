#!/bin/bash

## TODO: Add here the test suites that we want to execute. Ex.
# testSuites=(350178)
# testSuites=(245814 245815)
testSuites=(357469)

declare -A testsStatus
declare -A testsHashes
declare -A testsRequest

# Default Values
AppID=${ENDTEST_APP_ID:=""}
AppCode=${ENDTEST_APP_CODE:=""}
SERVER_URL=${SERVER_URL:=""}
PM_USER="admin"
PM_PASSWORD="admin"

echo "Sending tests to EndTest platform"
timeElapsed=0
exitCode=0
for test in "${testSuites[@]}"
do
    testsStatus[$test]=""
    testsHashes[$test]=""
    testsRequest[$test]="https://endtest.io/api.php?action=runWeb&appId=${AppID}&appCode=${AppCode}&suite=${test}&platform=windows&os=windows10&browser=chrome&resolution=1920x1200&geolocation=sanfrancisco&cases=all&notes=TestFromEndtest.cloud.processmaker.io&username=${PM_USER}&pwd=${PM_PASSWORD}&URL=${SERVER_URL}"

    echo "Get Hash for execution test suite id: $test"
    testsHashes[$test]=$(curl -s -X GET --header "Accept: */*" "${testsRequest[$test]}")
    echo "Hash: ${testsHashes[$test]}"
    # TODO: if not_allowed then show error
    pendingTest=true
    echo "Test in progress..."
    testTimeElapsed=0
    while $pendingTest; do
        sleep 60
        timeElapsed=$((timeElapsed + 1))
        testTimeElapsed=$((testTimeElapsed + 1))
        result=$(curl -s -X GET --header "Accept: */*" "https://endtest.io/api.php?action=getResults&appId=${AppID}&appCode=${AppCode}&hash=${testsHashes[$test]}&format=json")
        if [ "$result" == "Test is still running." ]
        then
            testsStatus[$test]=$result
            echo "Status: $result Time Elapsed of this test: $testTimeElapsed minutes."
            pendingTest=true
        elif [ "$result" == "Processing video recording." ]
        then
            testsStatus[$test]=$result
            echo "Status: $result Time Elapsed of this test: $testTimeElapsed minutes."
            pendingTest=true
        elif [ "$result" == "Stopping." ]
        then
            testsStatus[$test]=$result
            echo "Status: $result Time Elapsed of this test: $testTimeElapsed minutes."
            pendingTest=true
        elif [ "$result" == "Erred." ]
        then
            testsStatus[$test]=$result
            echo "TestSuite failed for ID: $test, Status: $result"
            echo "Time Elapsed of this test: $testTimeElapsed minutes."
            echo "Please check this link for detailed info: https://endtest.io/results?hash=${testsHashes[$test]}"
            pendingTest=false
            exitCode=1
        elif [ "$result" == "" ]
        then
            testsStatus[$test]=$result
            echo "Status: $result Time Elapsed of this test: $testTimeElapsed minutes."
        else
            # TODO - Nice to have: Upload results to CI
            echo "$result" > "$test".json
            pendingTest=false
            testsStatus[$test]="Completed"
            echo "Test completed for ID: $test. Time Elapsed of this test: $testTimeElapsed minutes."
        fi
    done
done

if [ $exitCode -ne 0 ]; then
    echo "TestSuites finished with Errors, please see logs above."
else
    echo "TestSuites finished successfully."
fi

echo "Elapsed time of all tests: $timeElapsed"
exit $exitCode
