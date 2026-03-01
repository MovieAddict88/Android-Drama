package shorty.drama.ronald.com;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class Episode {
    @SerializedName("chapterId")
    private String chapterId;
    @SerializedName("chapterIndex")
    private int chapterIndex;
    @SerializedName("chapterName")
    private String chapterName;
    @SerializedName("chapterImg")
    private String chapterImg;
    @SerializedName("cdnList")
    private List<CdnItem> cdnList;

    public String getChapterId() { return chapterId; }
    public int getChapterIndex() { return chapterIndex; }
    public String getChapterName() { return chapterName; }
    public String getChapterImg() { return chapterImg; }
    public List<CdnItem> getCdnList() { return cdnList; }

    public String getDefaultVideoUrl() {
        if (cdnList != null) {
            for (CdnItem cdn : cdnList) {
                if (cdn.getVideoPathList() != null) {
                    for (VideoPathItem path : cdn.getVideoPathList()) {
                        if (path.getIsDefault() == 1) {
                            return buildUrl(cdn.getCdnDomain(), path.getVideoPath());
                        }
                    }
                    if (!cdn.getVideoPathList().isEmpty()) {
                        return buildUrl(cdn.getCdnDomain(), cdn.getVideoPathList().get(0).getVideoPath());
                    }
                }
            }
        }
        return null;
    }

    private String buildUrl(String domain, String path) {
        if (path == null) return null;
        if (path.startsWith("http")) return path;
        if (domain == null) return path;

        String cleanDomain = domain.endsWith("/") ? domain.substring(0, domain.length() - 1) : domain;
        String cleanPath = path.startsWith("/") ? path : "/" + path;

        if (!cleanDomain.startsWith("http")) {
            cleanDomain = "https://" + cleanDomain;
        }

        return cleanDomain + cleanPath;
    }
}
