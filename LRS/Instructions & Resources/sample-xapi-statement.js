// SAMPLE xapi-Statement.js file
// COPY this file and make modifications

function sendStatement(verb, verbId, objectName, objectId, objectType, timestamp, resultData, courseName, courseId) {
    const player = GetPlayer();
    const userNamejs = player.GetVar("userName");
    const userEmailjs = player.GetVar("userEmail");

    // Log the user details
    console.log("User Name:", userNamejs);
    console.log("User Email:", userEmailjs);

    const conf = {
        "endpoint": 'http://localhost/discovery-lrs/lrs/api.php/',
        "auth": 'Basic ' + btoa('root:')
    };
    ADL.XAPIWrapper.changeConfig(conf);

    const statement = {
        "actor": {
            "name": userNamejs,
            "mbox": "mailto:" + userEmailjs
        },
        "verb": {
            "id": verbId,
            "display": {
                "en-US": verb
            }
        },
        "object": {
            "id": objectId,
            "objectType": objectType,
            "definition": {
                "name": {
                    "en-US": objectName
                },
                "description": {
                    "en-US": objectName
                }
            }
        },
        "result": resultData,
        "timestamp": timestamp,
        // Add courseName and courseId to the context
        "context": {
            "contextActivities": {
                "parent": [
                    {
                        "id": courseId,
                        "objectType": "Activity",
                        "definition": {
                            "name": {
                                "en-US": courseName
                            }
                        }
                    }
                ]
            }
        }
    };

    // Log the entire statement object
    console.log("Statement to be sent:", statement);

    ADL.XAPIWrapper.sendStatement(statement, function(resp) {
        // Log the response received after sending the statement
        if (resp && resp.success) {
            console.log("Statement sent successfully:", resp);
        } else {
            console.error("Error sending statement:", resp);
        }
    });
}
