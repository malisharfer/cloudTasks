from unittest.mock import patch, Mock
import pytest , datetime
from read_log_analytics import *


class response_partial_data:
    def __init__(self):
        self.partial_data = "partial_data"

class log_query_client:
    def query_workspace(self,workspace_id,query,timespan):
        return response_partial_data()


class mock_table:
    def __init__(self):
        self.rows = [[0,1,2],[1,1,2]]


@patch("read_log_analytics.get_times",Mock(return_value="times"))
@patch("read_log_analytics.get_max_times_log_for_each_storage")
def test_get_array_of_last_fetch_time(get_max_times_log_for_each_storage):
    get_array_of_last_fetch_time()
    get_max_times_log_for_each_storage.assert_called_once_with("times")


@patch("read_log_analytics.get_workspace_table",Mock(return_value="data_for_type_query"))
@patch("read_log_analytics.get_times_logs",Mock(return_value=["123","456"]))
def test_get_times():
    assert get_times()==["123","456","123","456","123","456","123","456"]


@patch("read_log_analytics.time_index","days")
@patch("read_log_analytics.time_period_for_check_last_fetch","30")
@patch("read_log_analytics.workspace_id","workspace_id")
@patch("read_log_analytics.return_seconds",Mock(return_value=datetime.date(2023,12,12)))
@patch("read_log_analytics.timedelta",Mock(return_value="30"))
@patch("read_log_analytics.create_log_query_client")
def test_get_workspace_table_check_Logs_query_client(create_log_query_client):
    get_workspace_table("type_query")
    create_log_query_client().query_workspace.assert_called_once_with(
        workspace_id="workspace_id",
        query="Storagetype_queryLogs",
        timespan="30"
    )


@patch("read_log_analytics.time_index","days")
@patch("read_log_analytics.time_period_for_check_last_fetch","30")
@patch("read_log_analytics.workspace_id","workspace_id")
@patch("read_log_analytics.return_seconds",Mock(return_value=datetime.date(2023,12,12)))
@patch("read_log_analytics.timedelta",Mock(return_value="30"))
@patch("read_log_analytics.create_log_query_client",Mock(return_value=log_query_client()))
def test_get_workspace_table_failed_attribute_error():
    assert get_workspace_table("type_query") == "partial_data"
    
@patch("read_log_analytics.time_index","days")
@patch("read_log_analytics.time_period_for_check_last_fetch","30")
@patch("read_log_analytics.workspace_id","workspace_id")
@patch("read_log_analytics.return_seconds",Mock(return_value=datetime.date(2023,12,12)))
@patch("read_log_analytics.timedelta",Mock(return_value="30"))
@patch("read_log_analytics.create_log_query_client",Mock(return_value="log_query_client"))
def test_get_workspace_table_failed_exception():
    with pytest.raises(Exception) as exception:
        get_workspace_table("type_query")
    assert "Failed to retrieve the data" in str(exception.value)


@patch("read_log_analytics.DefaultAzureCredential",Mock(return_value="default azure credential"))
@patch("read_log_analytics.LogsQueryClient")
def test_create_log_query_client(LogsQueryClient):
    create_log_query_client()
    LogsQueryClient.assert_called_once_with(
        credential="default azure credential"
    )


def test_return_seconds():
    assert return_seconds({"type_of_time": "days", "number": 10}) == 864000
    assert return_seconds({"type_of_time": "weeks", "number": 2}) == 1209600
    assert return_seconds({"type_of_time": "months", "number": 3}) == 7776000
    assert return_seconds({"type_of_time": "years", "number": 1}) == 31536000
    assert (
        return_seconds({"type_of_time": "f", "number": 1}) == -1
    ), "type date is not valid"


@patch('read_log_analytics.get_times_logs_per_table', Mock(side_effect = [[1,2],[3,4],[5,6]]))
def test_get_times_logs():
    assert get_times_logs([mock_table(),mock_table(),mock_table()]) == [1,2,3,4,5,6]


def test_get_times_logs_per_table():
    assert get_times_logs_per_table(mock_table()) == [{'storage_name':2,'time':1},{'storage_name':2,'time':1}]


class mock_time:
    def __init__(self,year,month,day):
        self.year = year
        self.month = month
        self.day = day
    def timestamp(self):
        return datetime.date(self.year,self.month,self.day)
    def __str__(self):
        return str(self.timestamp())
    
@patch('read_log_analytics.sorted',Mock(return_value="sorted_data"))
@patch('read_log_analytics.itemgetter',Mock(return_value="storage_name"))
@patch('read_log_analytics.groupby',Mock(return_value=[("storage_name",{"storage_name":"storage1","time":mock_time(2023,11,12)}),
                                                    ("storage_name",{"storage_name":"storage1","time":mock_time(2023,12,12)}),
                                                    ("storage_name",{"storage_name":"storage2","time":mock_time(2023,11,12)}),
                                                    ("storage_name",{"storage_name":"storage2","time":mock_time(2023,12,12)})]))
@patch('read_log_analytics.next',Mock(side_effect=[{"storage_name":"storage1","time":mock_time(2023,11,12)},
                                                    {"storage_name":"storage1","time":mock_time(2023,12,12)},
                                                    {"storage_name":"storage2","time":mock_time(2023,11,12)},
                                                    {"storage_name":"storage2","time":mock_time(2023,12,12)}]))

def test_get_max_times_log_for_each_storage():
    assert get_max_times_log_for_each_storage(
        [{"storage_name":"storage1","time":mock_time(2023,11,12)},
        {"storage_name":"storage1","time":mock_time(2023,12,12)},
        {"storage_name":"storage2","time":mock_time(2023,11,12)},
        {"storage_name":"storage2","time":mock_time(2023,12,12)}]) == [{'storage_name':"storage1",'time': str(mock_time(2023,11,12))},
                                                    {'storage_name':"storage1",'time':str(mock_time(2023,12,12))},
                                                    {'storage_name':"storage2",'time':str(mock_time(2023,11,12))},
                                                    {'storage_name':"storage2",'time':str(mock_time(2023,12,12))}]