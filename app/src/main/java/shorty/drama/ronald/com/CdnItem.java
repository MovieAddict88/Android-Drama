package shorty.drama.ronald.com;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class CdnItem {
    @SerializedName("cdnDomain")
    private String cdnDomain;
    @SerializedName("isDefault")
    private int isDefault;
    @SerializedName("videoPathList")
    private List<VideoPathItem> videoPathList;

    public String getCdnDomain() { return cdnDomain; }
    public int getIsDefault() { return isDefault; }
    public List<VideoPathItem> getVideoPathList() { return videoPathList; }
}
