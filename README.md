Hear is a API server for Wan2GP and the way it work is you setup the Wan2GP so you get a wgp_config.json and this is tested with it and make music </p>

this server runs on port 8001

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
