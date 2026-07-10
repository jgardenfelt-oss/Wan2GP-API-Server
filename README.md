Hear is a API server for Wan2GP and the way it work is you setup the Wan2GP so you get a wgp_config.json and this is tested with it and make music

Endpoints for the api server:
  POST /create_task        -> create a generation task, returns task_id
  POST /create_task_raw    -> create task from raw tags/text from external sites
  POST /parse_tags         -> parse raw tags into structured prompt + caption
  GET  /task_status/{id}   -> poll task status / progress / output
  POST /release_task/{id}  -> release / cleanup a finished task
  GET  /get_result/{id}    -> download the output file
  POST /cancel_task/{id}   -> cancel a running task
  GET  /list_tasks         -> list all tasks
  POST /generate           -> alias for create_task
  POST /run                -> blocking: submit and wait for result
