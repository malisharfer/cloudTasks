FROM mcr.microsoft.com/azure-functions/python:4-python3.10

ENV AzureWebJobsScriptRoot=/home/site/wwwroot \
    AzureFunctionsJobHost__Logging__Console__IsEnabled=true \
    AzureWebJobsFeatureFlags=EnableWorkerIndexing \ 
    AzureWebJobsStorage=UseDevelopmentStorage=true 
RUN pip install --trusted-host pypi.org --trusted-host pypi.python.org --trusted-host files.pythonhosted.org --root-user-action=ignore
COPY requirements.txt /
RUN pip install -r /requirements.txt
COPY . /home/site/wwwroot



