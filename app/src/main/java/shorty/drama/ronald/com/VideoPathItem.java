package shorty.drama.ronald.com;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class VideoPathItem {
    @SerializedName("quality")
    private int quality;
    @SerializedName("videoPath")
    private String videoPath;
    @SerializedName("isDefault")
    private int isDefault;

    public int getQuality() { return quality; }
    public String getVideoPath() { return videoPath; }
    public int getIsDefault() { return isDefault; }
}
