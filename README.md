Here is a API server for Wan2GP and the way it work is you setup the Wan2GP so you get a wgp_config.json and this is tested with it and make music </p>
and the repo there is use for this is here https://github.com/deepbeepmeep/Wan2GP</p>
this server runs on port 8001</p>
And the py and bat file need to be put in the root folder of the Wan2GP install folder

Endpoints for the api server:
</p>
  POST /create_task        -> create a generation task, returns task_id</p>
  POST /create_task_raw    -> create task from raw tags/text from external sites</p>
  POST /parse_tags         -> parse raw tags into structured prompt + caption</p>
  GET  /task_status/{id}   -> poll task status / progress / output</p>
  POST /release_task/{id}  -> release / cleanup a finished task</p>
  GET  /get_result/{id}    -> download the output file</p>
  POST /cancel_task/{id}   -> cancel a running task</p>
  GET  /list_tasks         -> list all tasks</p>
  POST /generate           -> alias for create_task</p>
  POST /run                -> blocking: submit and wait for result</p>
  </p>
  and if you wish to use it with extern ip you need to use the </p>
  username and password the server make for you</p>
  </p>
  </p>
  The Wan2GP Website is make in php so you can make music with this api server </p>
  
