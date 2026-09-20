export { }; // it is a module exporting nothing

let apiKey: string | null = null;
let basePath = '/';
let db: IDBDatabase | null = null;

function initDatabase() {
  console.debug('[initDatabase]');
  const request = indexedDB.open('slider', 2); // Remember to increase the database version in VidSlides.ts too, if changed!

  request.onupgradeneeded = () => {
    console.log("DB onupgradeneeded");
    console.log("Creating a new object store.");
    db = request.result;
    if (db && !db.objectStoreNames.contains("videos")) {
      db.createObjectStore("videos", { keyPath: "id" });
    }
  };

  request.onerror = (event) => {
    console.error('DB error');
    console.dir(event);
  };

  request.onsuccess = () => {
    console.debug('DB success!');
    db = request.result;
    console.dir(db);
    cleanUpDB(db);
  };
}
initDatabase();

/**
 * Checks which videos should be deleted from the browser indexedDB and deletes them.
 */
function cleanUpDB(db: IDBDatabase) {
  console.debug('[DB] Cleaning up old videos…');

  // Get all stored videos…
  const allVideos = db.transaction('videos', 'readonly').objectStore('videos');
  const getAllRequest = allVideos.getAll();

  getAllRequest.onsuccess = () => {
    const videos = getAllRequest.result;
    for (let i = 0; i < videos.length; i += 1) {
      if (!videos[i].expiration || videos[i].expiration < new Date().getTime()) {
        console.debug(`The cache has expired, deleting the video with id ${videos[i].id}`);
        const deleteVideo = db.transaction('videos', 'readwrite').objectStore('videos');
        deleteVideo.delete(videos[i].id);
      }
    }
  };
}

function downloadAndStoreVideo(id: number, expiration: number) {
  const videoPath = `${basePath}videos/${id}?api_token=${apiKey}`;
  fetch(videoPath)
    .then((response) => response.blob())
    .then((blob) => {
      console.dir(blob);
      if (!db) {
        throw new Error("Database not initialized");
      }
      const storeVideo = db.transaction('videos', 'readwrite').objectStore('videos');
      storeVideo.add({
        id,
        video: blob,
        created: new Date().getTime(),
        expiration,
      });
    });
}

function fetchVideoByID(id: number, expiration: number) {
  console.debug(`[VideoFetch] Checking if the video #${id} is already cached`);
  if (!db) {
    console.warn('Database is not ready, retrying in 5 seconds…');
    setTimeout(() => { fetchVideoByID(id, expiration); }, 5000);
    return;
  }
  console.debug('[VideoFetch] DB connection established');
  const videosTransaction = db.transaction('videos', 'readonly').objectStore('videos');
  const readVideo = videosTransaction.get(id);
  readVideo.onsuccess = () => {
    if (readVideo.result === undefined) {
      // The video was not cached yet
      console.debug(`[VideoFetch] Video #${id} was not cached, downloading…`);
      downloadAndStoreVideo(id, expiration);
    } else {
      console.debug(`The video #${id} was found in the cache`);
    }
  };
}

onmessage = function receiveMessage(e) {
  console.log('Videos Worker: Message received from main script');
  switch (e.data.action) {
    case 'FETCH': {
      const videoID = e.data.id;
      console.debug(`Fetching video #${videoID} from backend…`);
      fetchVideoByID(videoID, e.data.expiration);
      break;
    }
    case 'API': {
      apiKey = e.data.key;
      break;
    }
    case 'BASEPATH': {
      basePath = e.data.path;
      break;
    }
    default:
      console.error('Unexpected action');
  }
  const result = e.data[0] * e.data[1];
  if (Number.isNaN(result)) {
    postMessage('Please write two numbers');
  } else {
    const workerResult = `Result: ${result}`;
    console.log('Worker: Posting message back to main script');
    postMessage(workerResult);
  }
};

console.warn("videosWorker is running");