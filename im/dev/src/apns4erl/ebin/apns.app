{application, apns,
 [
  {description, ""},
  {vsn, "1.0"},
  {registered, []},
  {applications, [
                  kernel,
                  stdlib,
                  ssl
                 ]},
  {mod, {apns_app, []}},
  {env, [
         % {apple_host,       "gateway.sandbox.push.apple.com"},
         {apple_host,       "gateway.push.apple.com"},
         {apple_port,       2195},
         % {cert_file,        "/opt/fafa/lib/apns4erl/priv/ios_push_dev.pem"},
         {cert_file,        "/opt/fafa/lib/apns4erl/priv/ios_push_prod.pem"},
         {key_file,         undefined},
         {timeout,          30000},
         {feedback_host,    "feedback.sandbox.push.apple.com"},
         {feedback_port,    2196},
         {feedback_timeout, 600000} %% 10 Minutes
        ]}
 ]}.