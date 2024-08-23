// SAMPLE user.js file, to make modifications in Storyline User.js files.
// COPY the structure of the sendStatements when creating xAPI statements using Execute Javascript trigger.

function ExecuteScript(strId)
{
  switch (strId)
  {
      case "6qZMACcQntZ":
        Script1();
        break;
      case "6mtFjbgvCav":
        Script2();
        break;
      case "6eIKu1LdcpN":
        Script3();
        break;
      case "6T7wQJ0dOvs":
        Script4();
        break;
  }
}

function Script1()
{
  sendStatement(
    "answered",         
    "http://adlnet.gov/expapi/verbs/answered", 
    "Choice A",        
    "https://www.example.com/choice-a", 
    "Activity",             
    new Date().toISOString(),   
    {"success": false},
    "Introduction to XAPI",  // courseName
    "COURSE1234"  // courseId                      
);
}

function Script2()
{
  sendStatement(
    "answered",      
    "http://adlnet.gov/expapi/verbs/answered", 
    "Choice B",            
    "https://www.example.com/choice-b", 
    "Activity",       
    new Date().toISOString(),  
    {"success": false},
    "Introduction to XAPI",  // courseName
    "COURSE1234"  // courseId                    
);
}

function Script3()
{
  sendStatement(
    "answered",      
    "http://adlnet.gov/expapi/verbs/answered", 
    "Choice C",              
    "https://www.example.com/choice-c", 
    "Activity",              
    new Date().toISOString(),    
    {"success": true},
    "Introduction to XAPI",  // courseName
    "COURSE1234"  // courseId                         
);
}

function Script4()
{
  sendStatement(
    "viewed",
    "http://id.tincanapi.com/verb/viewed",
    "Cheatsheet",
    "https://www.example.com/cheatsheat",
    "Activity", 
    new Date().toISOString(),
    {},
    "Introduction to XAPI",  // courseName
    "COURSE1234"  // courseId
);
}

