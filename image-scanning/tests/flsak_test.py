import pytest

@pytest.fixture
def client():
    from app import app
    client = app.test_client()
    yield client
